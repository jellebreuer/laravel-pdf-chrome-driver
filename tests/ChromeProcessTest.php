<?php

use Breuer\ChromeDriver\ChromeProcess;
use Breuer\ChromeDriver\Platform;

function chromeCommand(string $userDataDir): array
{
    return [
        Platform::chromeHeadlessBinary(),
        '--headless',
        '--no-sandbox',
        '--no-first-run',
        '--no-zygote',
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
    $process = new ChromeProcess;
    $user_data_dir = createTempUserDataDir();

    try {
        $process->start(chromeCommand($user_data_dir));

        $response = $process->send('Target.createTarget', ['url' => 'about:blank']);

        expect($response['result']['targetId'])->toBeString()->not->toBeEmpty();
    } finally {
        $process->stop();
        @rmdir($user_data_dir);
    }
});

it('can create a target and attach to it', function () {
    $process = new ChromeProcess;
    $user_data_dir = createTempUserDataDir();

    try {
        $process->start(chromeCommand($user_data_dir));

        $target = $process->send('Target.createTarget', ['url' => 'about:blank']);
        $session = $process->send('Target.attachToTarget', [
            'targetId' => $target['result']['targetId'],
            'flatten' => true,
        ]);

        expect($session['result']['sessionId'])->toBeString()->not->toBeEmpty();
    } finally {
        $process->stop();
        @rmdir($user_data_dir);
    }
});

it('can set document content via cdp', function () {
    $process = new ChromeProcess;
    $user_data_dir = createTempUserDataDir();

    try {
        $process->start(chromeCommand($user_data_dir));

        $target = $process->send('Target.createTarget', ['url' => 'about:blank']);
        $session = $process->send('Target.attachToTarget', [
            'targetId' => $target['result']['targetId'],
            'flatten' => true,
        ]);
        $session_id = $session['result']['sessionId'];

        $process->send('Page.enable', [], $session_id);

        $frame_tree = $process->send('Page.getFrameTree', [], $session_id);
        $frame_id = $frame_tree['result']['frameTree']['frame']['id'];

        $response = $process->send('Page.setDocumentContent', [
            'frameId' => $frame_id,
            'html' => '<html><body>Hello</body></html>',
        ], $session_id);

        expect($response['id'])->toBeInt();
    } finally {
        $process->stop();
        @rmdir($user_data_dir);
    }
});

it('can generate a pdf via cdp', function () {
    $process = new ChromeProcess;
    $user_data_dir = createTempUserDataDir();

    try {
        $process->start(chromeCommand($user_data_dir));

        $target = $process->send('Target.createTarget', ['url' => 'about:blank']);
        $session = $process->send('Target.attachToTarget', [
            'targetId' => $target['result']['targetId'],
            'flatten' => true,
        ]);
        $session_id = $session['result']['sessionId'];

        $process->send('Page.enable', [], $session_id);

        $frame_tree = $process->send('Page.getFrameTree', [], $session_id);
        $frame_id = $frame_tree['result']['frameTree']['frame']['id'];

        $process->send('Page.setDocumentContent', [
            'frameId' => $frame_id,
            'html' => '<html><body><h1>PDF Test</h1></body></html>',
        ], $session_id);

        $response = $process->send('Page.printToPDF', [
            'landscape' => false,
            'printBackground' => true,
        ], $session_id);

        $pdf = base64_decode($response['result']['data']);
        expect($pdf)->toStartWith('%PDF-');
    } finally {
        $process->stop();
        @rmdir($user_data_dir);
    }
});

it('throws on cdp errors', function () {
    $process = new ChromeProcess;
    $user_data_dir = createTempUserDataDir();

    try {
        $process->start(chromeCommand($user_data_dir));
        $process->send('NonExistent.method');
    } finally {
        $process->stop();
        @rmdir($user_data_dir);
    }
})->throws(RuntimeException::class, 'CDP error:');

it('can stop without starting', function () {
    $process = new ChromeProcess;
    $process->stop();
    $process->stop();

    expect(true)->toBeTrue();
});
