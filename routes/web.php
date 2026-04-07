<?php

use Illuminate\Support\Facades\Route;
use Httpsnader1\DatabaseControllers\Controllers\DatabaseController;

Route::get('/db-check', function() { return 'Package routing is working!'; });

Route::prefix(config('database-controllers.route_prefix', 'database-controllers'))
    ->middleware(['web', \Httpsnader1\DatabaseControllers\Middleware\DatabaseControllersAuth::class, \Httpsnader1\DatabaseControllers\Middleware\DatabaseControllersLocale::class])
    ->name('database-controllers.')
    ->group(function () {

    Route::get('/login', [DatabaseController::class, 'login'])->name('login');
    Route::post('/login', [DatabaseController::class, 'authenticate'])->name('login.post');
    Route::post('/logout', [DatabaseController::class, 'logout'])->name('logout');

    Route::get('/', [DatabaseController::class, 'index'])->name('index');
    Route::get('/backup', [DatabaseController::class, 'backup'])->name('backup');
    Route::post('/backup/export', [DatabaseController::class, 'export'])->name('backup.export');
    Route::post('/backup/import', [DatabaseController::class, 'import'])->name('backup.import');
    Route::post('/backup/restore/{name}', [DatabaseController::class, 'restoreBackup'])->name('backup.restore');
    Route::post('/backup/exclude-tables', [DatabaseController::class, 'updateExcludedTables'])->name('backup.exclude-tables');
    Route::get('/backup/download/{name}', [DatabaseController::class, 'downloadBackup'])->name('backup.download');
    Route::delete('/backup/delete-all', [DatabaseController::class, 'deleteAllBackups'])->name('backup.delete-all');
    Route::delete('/backup/{name}', [DatabaseController::class, 'deleteBackup'])->name('backup.delete');
    Route::get('/switch-locale/{locale}', [DatabaseController::class, 'switchLocale'])->name('switch-locale');
    Route::get('/images/{filename}', [DatabaseController::class, 'serveImage'])->name('image.serve');

    Route::get('/table/{table}', [DatabaseController::class, 'show'])->name('table.show');
    Route::post('/table/{table}/truncate', [DatabaseController::class, 'truncate'])->name('table.truncate');
    Route::post('/table/{table}/bulk-delete', [DatabaseController::class, 'bulkDestroy'])->name('table.bulk-delete');
    Route::post('/table/{table}', [DatabaseController::class, 'store'])->name('table.store');
    Route::put('/table/{table}/{id}', [DatabaseController::class, 'update'])->name('table.update');
    Route::delete('/table/{table}/{id}', [DatabaseController::class, 'destroy'])->name('table.destroy');
});
