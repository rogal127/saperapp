<?php

use App\Http\Controllers\AssociationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\ExpeditionController;
use App\Http\Controllers\FindingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.auth')->group(function () {
    Route::get('/', fn () => view('home'))->name('home');

    Route::get('/findings/create', [FindingController::class, 'create'])->name('findings.create');
    Route::get('/findings/created', [FindingController::class, 'created'])->name('findings.created');
    Route::post('/findings', [FindingController::class, 'store'])->middleware('throttle:15,60')->name('findings.store');
    Route::get('/map', [FindingController::class, 'map'])->name('findings.map');
    Route::get('/api/findings', [FindingController::class, 'mapSearch'])->name('findings.api');
    Route::get('/findings/{id}/edit', [FindingController::class, 'edit'])->name('findings.edit');
    Route::put('/findings/{id}', [FindingController::class, 'update'])->name('findings.update');
    Route::delete('/findings/{id}', [FindingController::class, 'destroy'])->name('findings.destroy');
    Route::get('/findings/{id}/photos/{photoId}', [FindingController::class, 'photo'])->name('findings.photo');
    Route::get('/findings/{id}/photos/{photoId}/thumb', [FindingController::class, 'photoThumb'])->name('findings.photo.thumb');
    Route::get('/findings/{id}/report', [FindingController::class, 'report'])->name('findings.report');
    Route::get('/findings/{id}', [FindingController::class, 'show'])->name('findings.show');
    Route::post('/api/findings/{finding}/message', [FindingController::class, 'sendMessage'])->name('findings.message');
    Route::post('/api/findings/{finding}/like', [FindingController::class, 'toggleLike'])->name('findings.like');
    Route::get('/api/findings/{finding}/likes', [FindingController::class, 'likers'])->name('findings.likers');
    Route::post('/api/findings/{finding}/favorite', [FindingController::class, 'toggleFavorite'])->name('findings.favorite');
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
    // Stowarzyszenia (warstwa mapy widoczna tylko dla administratorów)
    Route::get('/api/associations', [AssociationController::class, 'index'])->name('associations.index');
    Route::post('/api/associations', [AssociationController::class, 'store'])->name('associations.store');
    Route::put('/api/associations/{associationId}', [AssociationController::class, 'update'])->name('associations.update');
    Route::delete('/api/associations/{associationId}', [AssociationController::class, 'destroy'])->name('associations.destroy');

    Route::get('/api/wkz-consents', [FindingController::class, 'wkzConsents'])->name('wkz-consents.index');
    Route::get('/api/finding-categories', [FindingController::class, 'findingCategories'])->name('finding-categories.index');

    // Poszukiwania (rajdy)
    Route::get('/expeditions', [ExpeditionController::class, 'index'])->name('expeditions.index');
    Route::get('/expeditions/create', [ExpeditionController::class, 'create'])->name('expeditions.create');
    Route::post('/expeditions', [ExpeditionController::class, 'store'])->name('expeditions.store');
    Route::get('/expeditions/{id}/edit', [ExpeditionController::class, 'edit'])->name('expeditions.edit');
    Route::post('/api/expeditions/import-mymaps', [ExpeditionController::class, 'importGoogleMyMaps'])->name('expeditions.import-mymaps');
    Route::get('/api/expeditions', [ExpeditionController::class, 'apiIndex'])->name('expeditions.api');
    Route::get('/api/expeditions/pending-count', [ExpeditionController::class, 'pendingCount'])->name('expeditions.pending-count');
    Route::get('/api/expeditions/map', [ExpeditionController::class, 'mapAreas'])->name('expeditions.map-areas');
    Route::post('/api/expeditions/join', [ExpeditionController::class, 'joinByCode'])->name('expeditions.join-code');
    Route::get('/api/expeditions/{id}/findings', [ExpeditionController::class, 'findings'])->name('expeditions.findings');
    Route::delete('/api/expeditions/{id}/findings/{findingId}', [ExpeditionController::class, 'removeFinding'])->name('expeditions.findings.remove');
    Route::post('/api/expeditions/{id}/findings-export', [ExpeditionController::class, 'startFindingsExport'])->name('expeditions.findings-export.start');
    Route::get('/api/expeditions/{id}/findings-export/{exportId}/progress', [ExpeditionController::class, 'findingsExportProgress'])->name('expeditions.findings-export.progress');
    Route::get('/api/expeditions/{id}/findings-export/{exportId}/download', [ExpeditionController::class, 'findingsExportDownload'])->name('expeditions.findings-export.download');
    Route::post('/api/expeditions/{id}/participants', [ExpeditionController::class, 'invite'])->name('expeditions.invite');
    Route::post('/api/expeditions/{id}/join', [ExpeditionController::class, 'requestJoin'])->name('expeditions.request-join');
    Route::post('/api/expeditions/{id}/participants/{participant}/accept', [ExpeditionController::class, 'acceptParticipant'])->name('expeditions.participants.accept');
    Route::post('/api/expeditions/{id}/participants/{participant}/decline', [ExpeditionController::class, 'declineParticipant'])->name('expeditions.participants.decline');
    Route::delete('/api/expeditions/{id}/participants/{participant}', [ExpeditionController::class, 'removeParticipant'])->name('expeditions.participants.remove');
    Route::get('/api/expeditions/{id}', [ExpeditionController::class, 'apiShow'])->name('expeditions.api-show');
    Route::put('/api/expeditions/{id}', [ExpeditionController::class, 'update'])->name('expeditions.update');
    Route::delete('/api/expeditions/{id}', [ExpeditionController::class, 'destroy'])->name('expeditions.destroy');
    Route::get('/expeditions/{id}', [ExpeditionController::class, 'show'])->name('expeditions.show');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/api/profile/favorites', [ProfileController::class, 'favorites'])->name('profile.favorites');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar');
    Route::post('/profile/wkz-consents', [ProfileController::class, 'storeWkzConsent'])->name('profile.wkz-consents.store');
    Route::put('/profile/wkz-consents/{id}', [ProfileController::class, 'updateWkzConsent'])->name('profile.wkz-consents.update');
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

    Route::get('/chat', [ChatController::class, 'show'])->name('chat.show');
    Route::get('/api/chat/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/api/chat/messages', [ChatController::class, 'send'])->name('chat.send');
    Route::get('/api/chat/unread-count', [ChatController::class, 'unreadCount'])->name('chat.unread');

    Route::get('/users', [UserProfileController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [UserProfileController::class, 'show'])->name('users.show');
    Route::get('/users/{id}/findings', [UserProfileController::class, 'findings'])->name('users.findings');
    Route::delete('/users/{id}', [UserProfileController::class, 'destroy'])->name('users.destroy');

    Route::post('/logout', LogoutController::class)->name('logout');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.update');
});
