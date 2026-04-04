<?php

use function Pest\Laravel\get;

it('can test', function () {
    expect(true)->toBeTrue();
});

it('can inline a pdf', function () {
    $response = get('/pdf');

    $response
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertSeeText('PDF-1.4');
});

it('can inline a pdf with a name', function () {
    $response = get('/pdf-named');

    $response
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertSeeText('PDF-1.4');
});

it('can download a pdf', function () {
    $response = get('/pdf-download');

    $response
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'attachment; filename="download.pdf"')
        ->assertSeeText('PDF-1.4');
});
