<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Roda a cada minuto para mandar notificações de lembretes
Schedule::command('app:enviar-lembretes-agendamento')->everyMinute();

// Roda a cada hora para limpar a agenda
Schedule::command('app:finalizar-agendamentos')->hourly();