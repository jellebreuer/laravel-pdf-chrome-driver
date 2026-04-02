<?php

use Breuer\MakePDF\ChromeProcess;
use Breuer\MakePDF\Client;

beforeEach(function () {
    $this->process = new ChromeProcess;
});

afterEach(function () {
    $this->process->stop();
});

function chromeCommand(string $userDataDir): array
{
    $binary = Client::chromeHeadlessBinary();

    return [
        $binary,
        '--headless',
        '--disable-gpu',
        '--no-sandbox',
        '--no-first-run',
        '--disable-extensions',
        '--user-data-dir='.$userDataDir,
        '--remote-debugging-pipe',
    ];
}

function createTempUserDataDir(): string
{
    $dir = sys_get_temp_dir().'/chrome-process-test-'.bin2hex(random_bytes(8));
    mkdir($dir, 0777, true);

    return $dir;
}

it('can send cdp commands and receive responses', function () {
    $userDataDir = createTempUserDataDir();

    try {
        $this->process->start(chromeCommand($userDataDir));

        $response = $this->process->send('Target.createTarget', ['url' => 'about:blank']);

        expect($response['result']['targetId'])->toBeString()->not->toBeEmpty();
    } finally {
        $this->process->stop();
        @rmdir($userDataDir);
    }
});

it('can create a target and attach to it', function () {
    $userDataDir = createTempUserDataDir();

    try {
        $this->process->start(chromeCommand($userDataDir));

        $target = $this->process->send('Target.createTarget', ['url' => 'about:blank']);
        $session = $this->process->send('Target.attachToTarget', [
            'targetId' => $target['result']['targetId'],
            'flatten' => true,
        ]);

        expect($session['result']['sessionId'])->toBeString()->not->toBeEmpty();
    } finally {
        $this->process->stop();
        @rmdir($userDataDir);
    }
});

it('can set document content via cdp', function () {
    $userDataDir = createTempUserDataDir();

    try {
        $this->process->start(chromeCommand($userDataDir));

        $target = $this->process->send('Target.createTarget', ['url' => 'about:blank']);
        $session = $this->process->send('Target.attachToTarget', [
            'targetId' => $target['result']['targetId'],
            'flatten' => true,
        ]);
        $session_id = $session['result']['sessionId'];

        $this->process->send('Page.enable', [], $session_id);

        $frame_tree = $this->process->send('Page.getFrameTree', [], $session_id);
        $frame_id = $frame_tree['result']['frameTree']['frame']['id'];

        $response = $this->process->send('Page.setDocumentContent', [
            'frameId' => $frame_id,
            'html' => '<html><body>Hello</body></html>',
        ], $session_id);

        expect($response['id'])->toBeInt();
    } finally {
        $this->process->stop();
        @rmdir($userDataDir);
    }
});

it('can generate a pdf via cdp', function () {
    $userDataDir = createTempUserDataDir();

    try {
        $this->process->start(chromeCommand($userDataDir));

        $target = $this->process->send('Target.createTarget', ['url' => 'about:blank']);
        $session = $this->process->send('Target.attachToTarget', [
            'targetId' => $target['result']['targetId'],
            'flatten' => true,
        ]);
        $session_id = $session['result']['sessionId'];

        $this->process->send('Page.enable', [], $session_id);

        $frame_tree = $this->process->send('Page.getFrameTree', [], $session_id);
        $frame_id = $frame_tree['result']['frameTree']['frame']['id'];

        $this->process->send('Page.setDocumentContent', [
            'frameId' => $frame_id,
            'html' => '<html><body><h1>PDF Test</h1></body></html>',
        ], $session_id);

        $response = $this->process->send('Page.printToPDF', [
            'landscape' => false,
            'printBackground' => true,
        ], $session_id);

        $pdf = base64_decode($response['result']['data']);
        expect($pdf)->toStartWith('%PDF-');
    } finally {
        $this->process->stop();
        @rmdir($userDataDir);
    }
});

it('throws on cdp errors', function () {
    $userDataDir = createTempUserDataDir();

    try {
        $this->process->start(chromeCommand($userDataDir));

        $this->process->send('NonExistent.method');
    } finally {
        $this->process->stop();
        @rmdir($userDataDir);
    }
})->throws(\RuntimeException::class, 'CDP error:');

it('can stop without starting', function () {
    $this->process->stop();
    $this->process->stop();

    expect(true)->toBeTrue();
});
