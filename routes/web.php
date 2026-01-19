<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rota para o Admin aprovar o Estabelecimento
Route::get('/aprovar-estabelecimento/{id}', function ($id) {
    return view('pages.processar-aprovacao', [
        'endpoint' => "/api/admin/aprovar-estabelecimento/{$id}",
        'titulo' => 'Aprovando Estabelecimento...'
    ]);
})->name('web.aprovar.estabelecimento');

// Rota para o Dono aprovar o Profissional
Route::get('/confirmar-profissional/{id}', function ($id) {
    return view('pages.processar-aprovacao', [
        'endpoint' => "/api/estabelecimento/aprovar-profissional/{$id}",
        'titulo' => 'Confirmando Profissional...'
    ]);
})->name('web.confirmar.profissional');