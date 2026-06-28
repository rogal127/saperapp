<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\FindingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.auth')->group(function () {
    Route::get('/', fn () => view('home'))->name('home');

    Route::get('/findings/create', [FindingController::class, 'create'])->name('findings.create');
    Route::get('/findings/created', [FindingController::class, 'created'])->name('findings.created');
    Route::post('/findings', [FindingController::class, 'store'])->name('findings.store');
    Route::get('/map', [FindingController::class, 'map'])->name('findings.map');
    Route::get('/api/findings', [FindingController::class, 'mapSearch'])->name('findings.api');
    Route::get('/findings/{id}/edit', [FindingController::class, 'edit'])->name('findings.edit');
    Route::put('/findings/{id}', [FindingController::class, 'update'])->name('findings.update');
    Route::delete('/findings/{id}', [FindingController::class, 'destroy'])->name('findings.destroy');
    Route::get('/findings/{id}/photos/{photoId}', [FindingController::class, 'photo'])->name('findings.photo');
    Route::get('/findings/{id}/report', [FindingController::class, 'report'])->name('findings.report');
    Route::get('/findings/{id}', [FindingController::class, 'show'])->name('findings.show');
    Route::post('/api/findings/{finding}/message', [FindingController::class, 'sendMessage'])->name('findings.message');
    Route::post('/api/findings/{finding}/like', [FindingController::class, 'toggleLike'])->name('findings.like');
    Route::get('/api/findings/{finding}/likes', [FindingController::class, 'likers'])->name('findings.likers');
    Route::get('/api/findings/{finding}/comments', [FindingController::class, 'comments'])->name('findings.comments');
    Route::post('/api/findings/{finding}/comments', [FindingController::class, 'storeComment'])->name('findings.comments.store');
    Route::delete('/api/findings/{finding}/comments/{comment}', [FindingController::class, 'destroyComment'])->name('findings.comments.destroy');
    Route::get('/browse', [FindingController::class, 'browse'])->name('findings.browse');
    Route::get('/api/browse', [FindingController::class, 'browseApi'])->name('findings.browse.api');
    Route::get('/api/voivodeships', [FindingController::class, 'voivodeships'])->name('voivodeships.index');
    Route::get('/api/users/search', [FindingController::class, 'searchUsers'])->name('users.search');

    Route::get('/api/pins', [FindingController::class, 'pins'])->name('pins.index');
    Route::put('/api/pins/{pinId}', [FindingController::class, 'updatePin'])->name('pins.update');
    Route::get('/api/pins/{pinId}/findings', [FindingController::class, 'pinFindings'])->name('pins.findings');
    Route::get('/api/wkz-consents', [FindingController::class, 'wkzConsents'])->name('wkz-consents.index');
    Route::get('/api/finding-categories', [FindingController::class, 'findingCategories'])->name('finding-categories.index');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar');
    Route::post('/profile/wkz-consents', [ProfileController::class, 'storeWkzConsent'])->name('profile.wkz-consents.store');
    Route::delete('/profile/wkz-consents/{id}', [ProfileController::class, 'destroyWkzConsent'])->name('profile.wkz-consents.destroy');
    Route::get('/profile/wkz-consents/{id}/report', [ProfileController::class, 'wkzConsentReport'])->name('profile.wkz-consents.report');
    Route::post('/profile/findings-export', [ProfileController::class, 'startFindingsExport'])->name('profile.findings-export.start');
    Route::get('/profile/findings-export/{exportId}/progress', [ProfileController::class, 'findingsExportProgress'])->name('profile.findings-export.progress');
    Route::get('/profile/findings-export/{exportId}/download', [ProfileController::class, 'findingsExportDownload'])->name('profile.findings-export.download');

    Route::post('/messages/with/{userId}', [ConversationController::class, 'startWith'])->name('messages.start-with');
    Route::get('/messages', [ConversationController::class, 'index'])->name('messages.index');
    Route::get('/messages/{id}', [ConversationController::class, 'show'])->name('messages.show');
    Route::post('/api/conversations/{id}/messages', [ConversationController::class, 'send'])->name('messages.send');
    Route::get('/api/conversations/unread-count', [ConversationController::class, 'unreadCount'])->name('messages.unread');

    Route::get('/users/{id}', [UserProfileController::class, 'show'])->name('users.show');

    Route::post('/logout', LogoutController::class)->name('logout');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});
