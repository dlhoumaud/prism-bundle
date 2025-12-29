<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure;

use Psr\Log\LoggerInterface;
use Stringable;

/**
 * Fake Repository : Logger pour les tests
 */
final class FakeLogger implements LoggerInterface
{
    /** @var array<array{level: string, message: string, context: array<string, mixed>}> */
    private array $logs = [];

    /**
     * @param string|\Stringable $message
     */
    public function emergency($message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * @param string|\Stringable $message
     */
    public function alert($message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * @param string|\Stringable $message
     */
    public function critical($message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * @param string|\Stringable $message
     */
    public function error($message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * @param string|\Stringable $message
     */
    public function warning($message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * @param string|\Stringable $message
     */
    public function notice($message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * @param string|\Stringable $message
     */
    public function info($message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * @param string|\Stringable $message
     */
    public function debug($message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * @param mixed $level
     * @param string|\Stringable $message
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => is_string($level) ? $level : (string) $level, // @phpstan-ignore-line
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function getLogsByLevel(string $level): array
    {
        return array_values(array_filter(
            $this->logs,
            fn(array $log) => $log['level'] === $level
        ));
    }

    public function hasLog(string $message, string $level = null): bool
    {
        foreach ($this->logs as $log) {
            if ($level !== null && $log['level'] !== $level) {
                continue;
            }

            if (str_contains($log['message'], $message)) {
                return true;
            }
        }

        return false;
    }

    public function reset(): void
    {
        $this->logs = [];
    }
}
