<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\PaymentPlanController;
use App\Http\Controllers\SalesOfferController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ReservationFormController;
use App\Http\Controllers\SpaController;
use App\Http\Controllers\UnitHoldController;
use App\Http\Controllers\OneTimeLinkController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\BrokerController;

// User Management
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Route::post('/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Building Management
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/buildings', [BuildingController::class, 'index']);
    Route::post('/buildings', [BuildingController::class, 'store']);
    Route::get('/buildings/{id}', [BuildingController::class, 'show']);
    Route::put('/buildings/{id}', [BuildingController::class, 'update']);
    Route::delete('/buildings/{id}', [BuildingController::class, 'destroy']);
});

// Unit Management
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/units', [UnitController::class, 'index']);
    Route::post('/units', [UnitController::class, 'store']);
    Route::get('/units/{id}', [UnitController::class, 'show']);
    Route::put('/units/{id}', [UnitController::class, 'update']);
    Route::delete('/units/{id}', [UnitController::class, 'destroy']);
    Route::post('/units/{id}/approve', [UnitController::class, 'approve']);
});

// Payment Plans
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/payment-plans', [PaymentPlanController::class, 'store']);
    Route::get('/payment-plans/{id}', [PaymentPlanController::class, 'show']);
    Route::put('/payment-plans/{id}', [PaymentPlanController::class, 'update']);
    Route::delete('/payment-plans/{id}', [PaymentPlanController::class, 'destroy']);
    Route::get('/units/{id}/payment-plans', [PaymentPlanController::class, 'getPlansForUnit']);
});

// Sales Offer
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/sales-offers/generate', [SalesOfferController::class, 'generate']);
});

// Bookings
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bookings/scan-passport', [BookingController::class, 'scanPassport']);
    Route::post('/bookings/book-unit', [BookingController::class, 'bookUnit']);
    Route::post('/bookings/{id}/upload-receipt', [BookingController::class, 'uploadReceipt']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);
    Route::get('/bookings/{booking}/download-document', [BookingController::class, 'downloadDocument']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings/{id}/approve', [BookingController::class, 'approveBooking']);
});

// Reservation Forms
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bookings/{id}/reservation-form', [ReservationFormController::class, 'generate']);
    Route::post('/reservation-forms/{id}/upload-signed', [ReservationFormController::class, 'uploadSigned']);
    Route::post('/reservation-forms/{id}/approve', [ReservationFormController::class, 'approve']);
});

// Sales and Purchase Agreement (SPAs)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bookings/{id}/spa', [SpaController::class, 'generate']);
    Route::post('/spa/{id}/upload-signed', [SpaController::class, 'uploadSigned']);
    Route::post('/spa/{id}/approve', [SpaController::class, 'approve']);
});

// Unit Hold
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/units/{id}/hold', [UnitHoldController::class, 'hold']);
    Route::post('/units/{id}/hold/approve', [UnitHoldController::class, 'approveHold']);
    Route::post('/units/{id}/hold/reject', [UnitHoldController::class, 'rejectHold']);
});

// One-Time Links
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/otls', [OneTimeLinkController::class, 'index']);
    Route::post('/otls/generate', [OneTimeLinkController::class, 'generateLink']);
    Route::post('/otls/register', [OneTimeLinkController::class, 'registerUser']);
    Route::post('/users/{id}/approve', [OneTimeLinkController::class, 'approve']);
});

// Roles & Permissions
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/roles', [RolePermissionController::class, 'index']);
    Route::put('/roles/{role}', [RolePermissionController::class, 'updateRolePermissions']);
    Route::post('/roles', [RolePermissionController::class, 'storeRole']);
    Route::put('/users/{user}/role', [RolePermissionController::class, 'changeUserRole']);
    Route::delete('/roles/{role}', [RolePermissionController::class, 'destroy']);
});

// User Management
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserManagementController::class, 'listAllUsers']);
    Route::get('/users/{id}', [UserManagementController::class, 'getUserDetails']);
    Route::post('/users', [UserManagementController::class, 'registerUser']);
    Route::put('/users/{id}', [UserManagementController::class, 'updateUser']);
    Route::delete('/users/{id}', [UserManagementController::class, 'deleteUser']);
    Route::post('/users/change-password', [UserManagementController::class, 'changePassword']);
    Route::put('/users/{id}/activate', [UserManagementController::class, 'activate']);
    Route::put('/users/{id}/deactivate', [UserManagementController::class, 'deactivate']);
    Route::get('/users/docs/{id}', [UserManagementController::class, 'downloadDoc']);
});

// Brokers
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/brokers/upload-agreement', [BrokerController::class, 'uploadSignedAgreement']);
});
