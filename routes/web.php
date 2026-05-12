<?php

use App\Http\Controllers\DocumentDownloadController;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

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

/*
| Rota só para validar SetLocale + __() em testes (Feature). Não existe em staging/produção
| com APP_ENV típico — ver tests/Feature/LocaleTest.php.
*/
if (app()->environment('local', 'testing')) {
    Route::get('/_testing/locale-smoke', function (): Response {
        return response(
            '<!DOCTYPE html><html lang="'.e(str_replace('_', '-', app()->getLocale())).'"><body>'
            .'<h1 id="locale-smoke">'.e(__('messages.welcome.heading')).'</h1>'
            .'</body></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    })->name('testing.locale-smoke');
}
