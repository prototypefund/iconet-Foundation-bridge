<?php

use App\Http\Controllers\ActivityPubInbox;
use App\Http\Controllers\IconetInbox;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Webfinger;
use App\Http\Middleware\ActivityContentType;
use App\Http\Middleware\VerifyHttpSignature;
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

Route::middleware([ActivityContentType::class])->group(function () {
    Route::prefix('.well-known/webfinger')->group(function () {
        Route::controller(Webfinger::class)->group(function () {
            Route::get('/', 'query');
        });
    });

    Route::get('user/{user}', [UserController::class, 'show'])->name('profile');
});

Route::post('inbox/{user?}', [ActivityPubInbox::class, 'postActivity'])
    ->name('inbox')
    ->middleware(VerifyHttpSignature::class);

Route::post('/iconet', [IconetInbox::class, 'post']);
