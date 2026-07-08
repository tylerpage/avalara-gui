<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderReviewController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
Route::post('/settings/test-shopware', [SettingsController::class, 'testShopware'])->name('settings.test-shopware');
Route::post('/settings/test-avalara', [SettingsController::class, 'testAvalara'])->name('settings.test-avalara');
Route::post('/settings/test-authnet', [SettingsController::class, 'testAuthnet'])->name('settings.test-authnet');

Route::get('/orders/review-queue', [OrderReviewController::class, 'queue'])->name('orders.review-queue');
Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/{orderId}', [OrderController::class, 'show'])->name('orders.show');
Route::put('/orders/{orderId}/review', [OrderReviewController::class, 'update'])->name('orders.review.update');
Route::put('/orders/{orderId}/review/outcome', [OrderReviewController::class, 'updateOutcome'])->name('orders.review.outcome');
Route::post('/orders/{orderId}/review/tomorrow', [OrderReviewController::class, 'setTomorrow'])->name('orders.review.tomorrow');
Route::post('/orders/{orderId}/review/do-not-review', [OrderReviewController::class, 'markDoNotReview'])->name('orders.review.do-not-review');
Route::delete('/orders/{orderId}/review', [OrderReviewController::class, 'destroy'])->name('orders.review.destroy');
