<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\FindingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.auth')->group(function () {
    Route::get('/', fn () => view('home'))->name('home');

    Route::get('/findings/create', [FindingController::class, 'create'])->name('findings.create');
    Route::post('/findings', [FindingController::class, 'store'])->name('findings.store');
    Route::get('/map', [FindingController::class, 'map'])->name('findings.map');
    Route::get('/api/findings', [FindingController::class, 'mapSearch'])->name('findings.api');
    Route::post('/api/findings/{finding}/message', [FindingController::class, 'sendMessage'])->name('findings.message');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar');

    Route::get('/messages', [ConversationController::class, 'index'])->name('messages.index');
    Route::get('/messages/{id}', [ConversationController::class, 'show'])->name('messages.show');
    Route::post('/api/conversations/{id}/messages', [ConversationController::class, 'send'])->name('messages.send');
    Route::get('/api/conversations/unread-count', [ConversationController::class, 'unreadCount'])->name('messages.unread');

    Route::post('/logout', LogoutController::class)->name('logout');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});
