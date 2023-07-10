<?php

namespace RunAsRoot\Rooter\Manager;

use RunAsRoot\Rooter\Repository\EnvironmentRepository;

class PortManager
{
    private ?array $reservedPorts = null;
    private ?array $environmentPorts = null;

    private int $portOffset = 1024; // Offset to get a port number in the desired range
    private int $portRange = 65535; // Range of port numbers
    private array $ranges = [
        'default' => [1024, 65535],
        'HTTP' => [8001, 8400],
        'HTTPS' => [8401, 8999],
        'DB' => [3300, 3700],
        'MAILHOG_SMTP' => [10026, 10426],
        'MAILHOG_UI' => [18026, 22026],
        'REDIS' => [6379, 6779],
        'AMQP' => [5672, 5999],
        'AMQP_MANAGEMENT' => [15672, 19672],
        'ELASTICSEARCH' => [9200, 9600],
    ];

    public function __construct(private readonly EnvironmentRepository $environmentRepository)
    {
    }

    /** @throws \Exception */
    public function findFreePort(string $type = 'default'): int
    {
        $portRange = $this->getPortRangeByKey($type);

        $port = random_int($portRange[0], $portRange[1]);
        while (!$this->isPortAvailable($port)) {
            $port = random_int($this->portOffset, $this->portRange);
        }

        return $port;
    }

    /** check if a port is available and not reserved */
    public function isPortAvailable(int $port): bool
    {
        if (in_array($port, $this->getReservedPorts(), true)) {
            return false;
        }

        // check ports from known environments
        if (in_array($port, $this->getEnvironmentPorts(), true)) {
            return false;
        }

        $socket = @fsockopen('localhost', $port);
        if ($socket) {
            // when we were able to open a socket connection, the port is in use
            fclose($socket);
            return false;
        }
        return true;
    }

    private function getReservedPorts(): array
    {
        $this->reservedPorts = $this->reservedPorts ?? $this->initReservedPorts();
        return $this->reservedPorts;
    }

    /** Read the list of reserved ports from /etc/services */
    private function initReservedPorts(): array
    {
        $reservedPorts = [];
        $lines = file('/etc/services', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with($line, '#')) {
                continue;
            }

            $parts = preg_split('/\s+/', $line);
            if (isset($parts[1])) {
                $reservedPorts[] = $parts[1];
            }
        }
        return $reservedPorts;
    }

    private function getEnvironmentPorts(): array
    {
        $this->environmentPorts = $this->environmentPorts ?? $this->initEnvironmentPorts();
        return $this->environmentPorts;
    }

    /** Read the list of environments ports */
    private function initEnvironmentPorts(): array
    {
        $ports = [];

        $environments = $this->environmentRepository->getList();

        foreach ($environments as $environment) {
            foreach ($environment as $key => $value) {
                if (!str_contains($key, 'Port')) {
                    continue;
                }
                $ports[] = $value;
            }
        }

        return $ports;
    }

    private function getPortRangeByKey(string $key): array
    {
        if (array_key_exists($key, $this->ranges)) {
            return $this->ranges[$key];
        }

        throw new \InvalidArgumentException("Unknown Type $key");
    }

}
