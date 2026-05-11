<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentDownloadController;

Route::get('/', function () {
    // Sem "welcome": entrada do produto é o login do painel.
    return redirect('/admin/login');
})->name('home');

Route::get('/docs/api', function () {
    return view('docs.api');
})->name('docs.api');

Route::middleware(['auth'])->group(function (): void {
    Route::get('/documents/{document}/download', [DocumentDownloadController::class, 'download'])
        ->name('documents.download');
});
