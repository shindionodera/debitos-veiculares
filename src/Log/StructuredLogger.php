<?php

namespace App\Log;

class StructuredLogger
{
    private string $logFile;

    public function __construct(string $logFile = null)
    {
        $this->logFile = $logFile ?? dirname(__DIR__, 2) . '/logs/search.log';
    }

    public function logSearch(?string $licensePlate, array $response): void
    {
        $entry = [
            'placa' => $this->maskPlate($licensePlate),
            'response' => $response,
        ];

        $line = $this->timestamp() . ' - ' . json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->writeLine($line);
    }

    private function timestamp(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }

    private function maskPlate(?string $licensePlate): ?string
    {
        if ($licensePlate === null) {
            return null;
        }

        $licensePlate = trim($licensePlate);
        $length = mb_strlen($licensePlate);
        $visible = mb_substr($licensePlate, 0, min(3, $length));
        $masked = str_repeat('*', max(0, $length - 3));

        return $visible . $masked;
    }

    private function writeLine(string $line): void
    {
        $directory = dirname($this->logFile);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create log directory: %s', $directory));
        }

        file_put_contents($this->logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
