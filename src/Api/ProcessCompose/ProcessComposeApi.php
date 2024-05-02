<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Api\ProcessCompose;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RunAsRoot\Rooter\Api\ProcessCompose\Exception\ApiException;
use RunAsRoot\Rooter\Api\ProcessCompose\Exception\ConnectionException;
use RunAsRoot\Rooter\Api\ProcessCompose\Exception\InvalidResponseException;
use RunAsRoot\Rooter\Api\ProcessCompose\Exception\NoProcessesException;
use RunAsRoot\Rooter\Api\ProcessCompose\Exception\NotAliveException;

readonly class ProcessComposeApi
{
    public function __construct(
        private Client $client = new Client()
    ) {
    }

    /** @throws ApiException|\JsonException */
    public function isAlive(array $envData): void
    {
        if (!array_key_exists('processComposeSocket', $envData)) {
            throw new ApiException(
                'processComposeSocket not found in envData, please update the devenv.nix file. ' .
                'devenv >= 1.0.5 does not support port based communication anymore.' .
                'devenv >= 1.0.5 requires a socket based communication.'
            );
        }

        try {
            $processComposeSocket = $envData['processComposeSocket'];
            $response = $this->client->get(
                "http://127.0.0.1/live", ['curl' => [CURLOPT_UNIX_SOCKET_PATH => $processComposeSocket,],]
            );
        } catch (GuzzleException $e) {
            throw new ConnectionException("could not connect to process-compose: {$e->getMessage()}", 0, $e);
        }

        $contents = $response->getBody()->getContents();

        $isAliveData = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($isAliveData) || !isset($isAliveData['status'])) {
            throw new InvalidResponseException('invalid response from process-compose');
        }
        if ($isAliveData['status'] !== 'alive') {
            throw new NotAliveException('process-compose is not running');
        }
    }

    /** @throws ApiException|\JsonException */
    public function getProcessList(array $envData): array
    {
        try {
            $processComposeSocket = $envData['processComposeSocket'];
            $response = $this->client->get(
                "http://127.0.0.1/processes", ['curl' => [CURLOPT_UNIX_SOCKET_PATH => $processComposeSocket,],]
            );
        } catch (GuzzleException $e) {
            throw new ConnectionException("could not connect to process-compose: {$e->getMessage()}", 0, $e);
        }

        $contents = $response->getBody()->getContents();

        $processComposeData = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($processComposeData) || !isset($processComposeData['data'])) {
            throw new InvalidResponseException('invalid response from process-compose');
        }
        $processData = $processComposeData['data'];
        if (!is_array($processData) || count($processData) === 0) {
            throw new NoProcessesException('no processes returned from process-compose');
        }

        usort($processData, static function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $processData;
    }
}
