<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [App\Http\Controllers\TextToSpeechController::class, 'index'])->name('tts.index');
Route::post('/convert', [App\Http\Controllers\TextToSpeechController::class, 'convert'])->name('tts.convert');
Route::get('/voices', [App\Http\Controllers\TextToSpeechController::class, 'getVoices'])->name('tts.voices');
