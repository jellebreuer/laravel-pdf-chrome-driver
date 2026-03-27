<?php

namespace Breuer\MakePDF\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class CleanupCommand extends Command
{
    public $signature = 'make-pdf:cleanup {--older-than=120 : Kill processes older than this many seconds}';

    public $description = 'Kill orphaned chromedriver and chrome-headless-shell processes';

    /** @var list<string> */
    protected array $processNames = [
        'chromedriver',
        'chrome-headless-shell',
        'chrome_crashpad',
    ];

    public function handle(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->error('This command is not supported on Windows.');

            return self::FAILURE;
        }

        $threshold = (int) $this->option('older-than');
        $killed = 0;

        foreach ($this->processNames as $processName) {
            $pids = $this->findOrphanedProcesses($processName, $threshold);

            foreach ($pids as $pid) {
                $this->warn("Killing {$processName} (PID: {$pid})");
                posix_kill($pid, SIGTERM);
                $killed++;
            }
        }

        $killed === 0
            ? $this->info('No orphaned processes found.')
            : $this->info("Killed {$killed} orphaned process(es).");

        return self::SUCCESS;
    }

    /** @return list<int> */
    protected function findOrphanedProcesses(string $name, int $olderThanSeconds): array
    {
        $result = Process::run('ps -eo pid,etime,comm');

        if ($result->failed()) {
            return [];
        }

        /** @var list<int> */
        return collect(explode("\n", $result->output()))
            ->filter(fn (string $line): bool => str_contains($line, $name))
            ->map(fn (string $line): ?array => preg_split('/\s+/', trim($line), 3) ?: null)
            ->filter(fn (?array $parts): bool => $parts !== null && count($parts) >= 3)
            ->filter(fn (array $parts): bool => $this->parseEtime($parts[1]) >= $olderThanSeconds)
            ->map(fn (array $parts): int => (int) $parts[0])
            ->values()
            ->all();
    }

    /**
     * Parse ps etime format to seconds.
     *
     * Formats: "MM:SS", "HH:MM:SS", "D-HH:MM:SS"
     */
    protected function parseEtime(string $etime): int
    {
        $days = 0;

        if (str_contains($etime, '-')) {
            [$days, $etime] = explode('-', $etime, 2);
            $days = (int) $days;
        }

        $parts = array_reverse(explode(':', $etime));

        $seconds = (int) $parts[0];
        $minutes = (int) ($parts[1] ?? 0);
        $hours = (int) ($parts[2] ?? 0);

        return ($days * 86400) + ($hours * 3600) + ($minutes * 60) + $seconds;
    }
}
