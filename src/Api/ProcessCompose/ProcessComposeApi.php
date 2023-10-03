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
        try {
            $processComposePort = $envData['processComposePort'];
            $response = $this->client->get("127.0.0.1:$processComposePort/live");
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
            $processComposePort = $envData['processComposePort'];
            $response = $this->client->get("127.0.0.1:$processComposePort/processes");
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

        return $processData;
    }
}
