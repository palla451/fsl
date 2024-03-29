<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('form',[\App\Http\Controllers\FileController::class,'index']);
Route::post('file/upload',[\App\Http\Controllers\FileController::class,'store'])->name('store');
Route::get('file/extract/{filename}',[\App\Http\Controllers\FileController::class,'extract']);

//Auth::routes();
//
//Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
