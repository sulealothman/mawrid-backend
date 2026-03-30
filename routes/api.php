<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FileOperationController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
    Route::post('/password/reset',  [AuthController::class, 'resetPassword']);
});



Route::middleware('auth:sanctum')->prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
});

Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/', [UserController::class, 'me']);
    Route::patch('/update', [UserController::class, 'update']);

    Route::patch('/update-password', [UserController::class, 'updatePassword']);


    Route::patch('/preferences', [UserController::class, 'updatePreferences']);
    Route::post('/preferences/reset', [UserController::class, 'resetPreferences']);

    Route::post('/avatar', [UserController::class, 'uploadAvatar']);
    Route::delete('/avatar', [UserController::class, 'removeAvatar']);


    Route::post('/deactivate', [UserController::class, 'deactivateAccount']);
});



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/kb', [KnowledgeBaseController::class, 'index']);
    Route::get('/kb/{kbId}', [KnowledgeBaseController::class, 'show']);
    Route::post('/kb', [KnowledgeBaseController::class, 'store']);
    Route::put('/kb/{kbId}', [KnowledgeBaseController::class, 'update']);
    Route::delete('/kb/{kbId}', [KnowledgeBaseController::class, 'remove']);           // soft delete
    Route::delete('/kb/{kbId}/force', [KnowledgeBaseController::class, 'destroy']); // hard delete
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get(
        '/kb/{knowledgeBase}/chats',
        [ChatController::class, 'index']
    );
    Route::get('/chats/{chatId}', [ChatController::class, 'show']);
    Route::post(
        '/kb/{knowledgeBase}/chats/store',
        [ChatController::class, 'store']
    );
    Route::put('/chats/{chat}', [ChatController::class, 'update']);
    Route::delete('/chats/{chat}', [ChatController::class, 'remove']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/kb/{kbId}/files', [FileController::class, 'index']);
    Route::get('/kb/{kbId}/files/{file}', [FileController::class, 'show']);
    Route::post('/kb/{kbId}/files', [FileController::class, 'store']);
    Route::post('/kb/{kbId}/files/upload', [FileController::class, 'upload']);
    Route::put('/files/{file}', [FileController::class, 'update']);
    Route::delete('/files/{file}', [FileController::class, 'remove']);
    Route::delete('/files/{file}/force', [FileController::class, 'delete']);
});

Route::prefix('internal')->group(function () {
    Route::post('/file-operations/webhook', [FileOperationController::class, 'webhook']);
    Route::post('/chat/finalize', [ChatController::class, 'finalize']);
})->withoutMiddleware(['auth:sanctum']);