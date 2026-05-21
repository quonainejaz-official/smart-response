<?php

declare(strict_types=1);

use App\Http\Controllers\ExampleController;
use Illuminate\Support\Facades\Route;

Route::get('/users', [ExampleController::class, 'index'])->name('users.index');
Route::post('/users', [ExampleController::class, 'store'])->name('users.store');
Route::delete('/users/{user}', [ExampleController::class, 'destroy'])->name('users.destroy');

Route::prefix('api')->group(function () {
    Route::get('/users', [ExampleController::class, 'index']);
    Route::post('/users', [ExampleController::class, 'store']);
    Route::delete('/users/{user}', [ExampleController::class, 'destroy']);
});
