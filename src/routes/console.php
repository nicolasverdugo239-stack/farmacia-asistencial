<?php

use Illuminate\Support\Facades\Schedule;

// Alertas de vencimiento todos los días antes de abrir la farmacia (RF05).
Schedule::command('farmacia:revisar-vencimientos')->dailyAt('07:00');

// Verificación diaria de la integridad del libro de controlados.
Schedule::command('farmacia:verificar-libro')->dailyAt('07:05');
