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
use App\Http\Controllers\HoldingController;
use App\Http\Controllers\DldDocumentController;
use App\Http\Controllers\OneTimeLinkController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\BrokerController;
use App\Http\Controllers\UnitUpdateController;
use App\Http\Controllers\NotificationController;

//Index
Route::get('/', function () {
    return redirect('api/documentation');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Route::post('/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/auth/logout', [AuthController::class, 'logout']);

// Building Management
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/buildings', [BuildingController::class, 'index']);
    Route::post('/buildings', [BuildingController::class, 'store']);
    Route::get('/buildings/{id}', [BuildingController::class, 'show']);
    Route::put('/buildings/{id}', [BuildingController::class, 'update']);
    Route::delete('/buildings/{id}', [BuildingController::class, 'destroy']);
    Route::get('/buildings/{buildingId}/units', [BuildingController::class, 'getUnitsByBuilding']);
    Route::get('/buildings/{id}/image', [BuildingController::class, 'showImage'])->name('buildings.image');
});

// Unit Management
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/units', [UnitController::class, 'index']);
    Route::post('/units', [UnitController::class, 'store']);
    Route::get('/units/{id}', [UnitController::class, 'show']);
    Route::put('/units/{id}', [UnitController::class, 'update']);
    Route::delete('/units/{id}', [UnitController::class, 'destroy']);
    Route::post('/units/{id}/approve', [UnitController::class, 'approve']);
    Route::post('/units/{id}/assign', [UnitController::class, 'assignUnit']);
    Route::get('/units/{id}/floor-plan', [UnitController::class, 'showFloorPlan'])->name('units.floor_plan');
    Route::post('/units/import', [UnitController::class, 'importUnits']);
});

// Payment Plans
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/payment-plans', [PaymentPlanController::class, 'index']);
    Route::post('/payment-plans', [PaymentPlanController::class, 'store']);
    Route::get('/payment-plans/{id}', [PaymentPlanController::class, 'show']);
    Route::put('/payment-plans/{id}', [PaymentPlanController::class, 'update']);
    Route::delete('/payment-plans/{id}', [PaymentPlanController::class, 'destroy']);
    Route::post('/payment-plans/{id}/make-default', [PaymentPlanController::class, 'makeDefault']);
});

// Sales Offer
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/sales-offers/generate', [SalesOfferController::class, 'generate']);
});

// Bookings
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bookings/scan-passport', [BookingController::class, 'scanPassport']);
    Route::post('/bookings/book-unit', [BookingController::class, 'bookUnit']);
    Route::post('/bookings/{id}/upload-document', [BookingController::class, 'uploadDocument']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);
    Route::get('/bookings/{booking}/download-document/{type}', [BookingController::class, 'downloadDocument'])
        ->where('type', 'passport|receipt|rf|signed_rf|spa|signed_spa|dld')
        ->name('bookings.download_document');
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings/{id}/approve', [BookingController::class, 'approveBooking']);
});

// Reservation Forms
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bookings/{id}/rf', [ReservationFormController::class, 'generate']);
    Route::post('/bookings/{id}/rf/upload-signed', [ReservationFormController::class, 'uploadSigned']);
    Route::post('/bookings/{id}/rf/approve', [ReservationFormController::class, 'approve']);
});

// Sales and Purchase Agreement (SPAs)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bookings/{id}/spa', [SpaController::class, 'generate']);
    Route::post('/bookings/{id}/spa/upload-signed', [SpaController::class, 'uploadSigned']);
    Route::post('/bookings/{id}/spa/approve', [SpaController::class, 'approve']);
});

// DLD Documents
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bookings/{booking}/dld', [DldDocumentController::class, 'store']);
});


// Unit Hold
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/units/{id}/hold', [HoldingController::class, 'hold']);
    Route::post('/units/hold/{id}/respond', [HoldingController::class, 'respondHold']);
});

// Holdings
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/holdings', [HoldingController::class, 'listHoldings']);
});

// One-Time Links
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/otls', [OneTimeLinkController::class, 'index']);
    Route::post('/otls/generate', [OneTimeLinkController::class, 'generateLink']);
    Route::post('/users/{id}/approve', [OneTimeLinkController::class, 'approve']);
});
Route::post('/otls/register', [OneTimeLinkController::class, 'selfRegisterUser']);

// Roles & Permissions
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/roles', [RolePermissionController::class, 'listRoles']);
    Route::get('/permissions', [RolePermissionController::class, 'listPermissions']);
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
    Route::get('/users/{id}/docs/', [UserManagementController::class, 'listUserDocs']);
    Route::get('/users/docs/{id}/download', [UserManagementController::class, 'downloadDoc']);
});

// Brokers
Route::post('/brokers/upload-signed-agreement', [BrokerController::class, 'uploadSignedAgreement']);

// Unit Updates
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/unit-updates', [UnitUpdateController::class, 'index']);
    Route::get('/units/{unitId}/updates', [UnitUpdateController::class, 'listUnitUpdates']);
    Route::get('/unit-updates/{updateId}', [UnitUpdateController::class, 'show']);
    Route::post('/units/{unitId}/updates', [UnitUpdateController::class, 'store']);
    Route::delete('/unit-updates/{updateId}', [UnitUpdateController::class, 'destroy']);
    Route::get('/unit-updates/{updateId}/download-attachment', [UnitUpdateController::class, 'downloadAttachment']);
});

// FCM token
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/device-token', [NotificationController::class, 'storeDeviceToken']);
    Route::post('/notify', [NotificationController::class, 'sendPushNotification']);
});
