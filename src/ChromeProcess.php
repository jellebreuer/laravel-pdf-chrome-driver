<?php

namespace Breuer\MakePDF;

use React\ChildProcess\Process;
use React\EventLoop\Loop;
use React\Promise\Deferred;
use React\Stream\WritableStreamInterface;

use function React\Async\await;

class ChromeProcess
{
    protected ?Process $process = null;

    protected int $messageId = 0;

    /** @var list<array{match: callable, resolver: Deferred<array<string, mixed>>}> */
    protected array $waitingForResponse = [];

    /**
     * @param  list<string>  $command
     * @param  array<string, string>  $env
     */
    public function start(array $command, array $env = []): void
    {
        $this->messageId = 0;
        $this->waitingForResponse = [];

        $cmd = 'exec '.implode(' ', array_map('escapeshellarg', $command));

        $this->process = new Process($cmd, null, $env ?: null, [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
            3 => ['pipe', 'r'],  // CDP write (we write commands to Chrome)
            4 => ['pipe', 'w'],  // CDP read (Chrome writes responses to us)
        ]);

        $this->process->start(Loop::get());

        // We only communicate over CDP pipes (3 and 4), close the rest
        $this->process->stdin?->close();
        $this->process->stdout?->close();
        $this->process->stderr?->close();

        // Listen for incoming CDP messages on pipe 4
        // Chrome sends null-byte delimited JSON messages
        $buffer = '';

        $this->process->pipes[4]->on('data', function (string $chunk) use (&$buffer): void {
            $buffer .= $chunk;

            while (($null_pos = strpos($buffer, "\0")) !== false) {
                $raw = substr($buffer, 0, $null_pos);
                $buffer = substr($buffer, $null_pos + 1);

                /** @var array<string, mixed>|null $message */
                $message = json_decode($raw, true);
                if (is_array($message)) {
                    $this->handleIncomingMessage($message);
                }
            }
        });
    }

    /**
     * Send a CDP command and wait for Chrome to respond.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function send(string $method, array $params = [], ?string $sessionId = null, float $timeout = 30): array
    {
        if ($this->process === null) {
            throw new \RuntimeException('Chrome process not started');
        }

        $id = ++$this->messageId;

        $message = ['id' => $id, 'method' => $method, 'params' => (object) $params];

        if ($sessionId !== null) {
            $message['sessionId'] = $sessionId;
        }

        // Write the command to Chrome over CDP pipe 3
        /** @var WritableStreamInterface $cdp_write */
        $cdp_write = $this->process->pipes[3];
        $cdp_write->write(json_encode($message)."\0");

        // Wait until Chrome sends back a response with the same id
        return $this->waitForMessage(
            fn (array $response) => ($response['id'] ?? null) === $id,
            $timeout
        );
    }

    /**
     * Wait for a specific CDP event from Chrome.
     *
     * @return array<string, mixed>
     */
    public function waitForEvent(string $event, float $timeout = 30): array
    {
        return $this->waitForMessage(
            fn (array $response) => ($response['method'] ?? null) === $event,
            $timeout
        );
    }

    public function stop(): void
    {
        if ($this->process === null) {
            return;
        }

        foreach ($this->process->pipes as $pipe) {
            $pipe->close();
        }

        $this->process->terminate();
        $this->process = null;
    }

    public function __destruct()
    {
        $this->stop();
    }

    /**
     * Block until an incoming message matches the given condition, or timeout.
     *
     * Registers a pending wait entry. When Chrome sends a message, handleIncomingMessage()
     * checks all pending entries and resolves the first match — which unblocks this method.
     *
     * @param  callable(array<string, mixed>): bool  $match
     * @return array<string, mixed>
     */
    protected function waitForMessage(callable $match, float $timeout): array
    {
        /** @var Deferred<array<string, mixed>> $resolver */
        $resolver = new Deferred;

        $this->waitingForResponse[] = ['match' => $match, 'resolver' => $resolver];

        $timer = Loop::addTimer($timeout, function () use ($resolver): void {
            $resolver->reject(new \RuntimeException('CDP timeout'));
        });

        try {
            /** @var array<string, mixed> */
            return await($resolver->promise());
        } finally {
            Loop::cancelTimer($timer);
        }
    }

    /**
     * Called when a complete CDP message arrives from Chrome.
     * Resolves (or rejects) the first matching pending wait entry.
     *
     * @param  array<string, mixed>  $message
     */
    protected function handleIncomingMessage(array $message): void
    {
        // CDP errors should reject the first pending wait
        if (isset($message['error'])) {
            foreach ($this->waitingForResponse as $i => $entry) {
                array_splice($this->waitingForResponse, $i, 1);
                $entry['resolver']->reject(
                    new \RuntimeException('CDP error: '.json_encode($message['error']))
                );

                return;
            }
        }

        foreach ($this->waitingForResponse as $i => $entry) {
            if ($entry['match']($message)) {
                array_splice($this->waitingForResponse, $i, 1);
                $entry['resolver']->resolve($message);

                return;
            }
        }
    }
}
