<?php

use Illuminate\Support\Facades\Route;
use Spatie\LaravelPdf\Facades\Pdf;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pdf', function () {
    return Pdf::view('test');
});

Route::get('/pdf-named', function () {
    return Pdf::view('test')->name('hello');
});

Route::get('/pdf-download', function () {
    return Pdf::view('test')->download();
});

Route::get('/pdf-headerfooter', function () {
    return Pdf::view('test')
        ->headerView('header')
        ->footerView('footer');
});
