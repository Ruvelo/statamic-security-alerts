<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Ruvelo\StatamicSecurityAlerts\Http\Controllers\SecurityAlertsController;

Route::get('security-alerts', [SecurityAlertsController::class, 'index'])->name('security-alerts.index');
Route::post('security-alerts/check', [SecurityAlertsController::class, 'check'])->name('security-alerts.check');
