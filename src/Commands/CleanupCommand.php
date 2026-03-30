<?php

namespace Breuer\MakePDF\Commands;

use Breuer\MakePDF\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use function Breuer\MakePDF\package_path;

class CleanupCommand extends Command
{
    public $signature = 'make-pdf:cleanup {--older-than=120 : Kill processes older than this many seconds}';

    public $description = 'Kill orphaned chromedriver and chrome-headless-shell processes';

    public function handle(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->error('This command is not supported on Windows.');

            return self::FAILURE;
        }

        $threshold = (int) $this->option('older-than');
        $killed = 0;

        /** @var array<string, string> */
        $binaries = [
            'chromedriver' => $this->searchablePath(Client::chromeDriverBinary()),
            'chrome-headless-shell' => $this->searchablePath(Client::chromeHeadlessBinary()),
        ];

        foreach ($binaries as $name => $binaryPath) {
            $pids = $this->findOrphanedProcesses($binaryPath, $threshold);

            foreach ($pids as $pid) {
                $this->warn("Killing {$name} (PID: {$pid})");
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
    protected function findOrphanedProcesses(string $binaryPath, int $olderThanSeconds): array
    {
        $result = Process::run('ps -eo pid,etime,args');

        if ($result->failed()) {
            return [];
        }

        /** @var list<int> */
        return collect(explode("\n", $result->output()))
            ->filter(fn (string $line): bool => str_contains($line, $binaryPath))
            ->map(fn (string $line): ?array => preg_split('/\s+/', trim($line), 3) ?: null)
            ->filter(fn (?array $parts): bool => $parts !== null && count($parts) >= 3)
            ->filter(fn (array $parts): bool => $this->parseEtime($parts[1]) >= $olderThanSeconds)
            ->map(fn (array $parts): int => (int) $parts[0])
            ->values()
            ->all();
    }

    /**
     * Get the path to match against in ps output.
     *
     * For default package binaries, use a relative path from the package root
     * so it matches across deployment releases. For custom config paths, use the full path.
     */
    protected function searchablePath(string $binaryPath): string
    {
        $packageRoot = package_path();
        $vendorPrefix = dirname($packageRoot, 3).'/';

        if (str_starts_with($binaryPath, $vendorPrefix)) {
            return substr($binaryPath, strlen($vendorPrefix));
        }

        return $binaryPath;
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
