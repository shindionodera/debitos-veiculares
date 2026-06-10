<?php

namespace Tests;

use App\Log\StructuredLogger;
use PHPUnit\Framework\TestCase;

class StructuredLoggerTest extends TestCase
{
    private string $logFile;
    private StructuredLogger $logger;

    protected function setUp(): void
    {
        $this->logFile = tempnam(sys_get_temp_dir(), 'structured_logger_test');

        if ($this->logFile === false) {
            $this->fail('Unable to create temporary log file.');
        }

        unlink($this->logFile);
        $this->logger = new StructuredLogger($this->logFile);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testLogsSearchWithMaskedPlateAndSingleLineJson(): void
    {
        $this->logger->logSearch('ABC1D23', ['status' => 'ok', 'value' => 123]);

        $contents = file_get_contents($this->logFile);
        $this->assertIsString($contents);

        $lines = array_filter(explode("\n", $contents), 'strlen');
        $this->assertCount(1, $lines);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} - \{.*\}$/', $lines[0]);

        $jsonPart = substr($lines[0], strpos($lines[0], ' - ') + 3);
        $decoded = json_decode($jsonPart, true);

        $this->assertSame([
            'placa' => 'ABC****',
            'response' => ['status' => 'ok', 'value' => 123],
        ], $decoded);
    }
}
