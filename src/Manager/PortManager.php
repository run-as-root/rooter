<?php

namespace RunAsRoot\Rooter\Manager;

class PortManager
{
    private ?array $reservedPorts = null;
    private int $portOffset = 1024; // Offset to get a port number in the desired range
    private int $portRange = 65535; // Range of port numbers

    private array $ranges = [
        'DEFAULT' => [1024, 65535],
        'HTTP' => [8001, 8400],
        'HTTPS' => [8401, 8999],
        'DB' => [3300, 3700],
        'MAILHOG_SMTP' => [10026, 10426],
        'MAILHOG_UI' => [18026, 22026],
        'REDIS' => [6379, 6779],
        'AMQP' => [5672, 5999],
        'AMQP_UI' => [15672, 19672],
        'ELASTIC' => [9200, 9600],
    ];

    /** @throws \Exception */
    public function findFreePort(): int
    {
        $port = random_int($this->portOffset, $this->portRange);
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

        // @todo check ports from known environments

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

}
