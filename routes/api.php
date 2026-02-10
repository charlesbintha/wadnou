<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ConsultationsController;
use App\Http\Controllers\Api\AppointmentsController;
use App\Http\Controllers\Api\NotificationsController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\DoctorsController;
use App\Http\Controllers\Api\Doctor\ConsultationsController as DoctorConsultationsController;
use App\Http\Controllers\Api\Doctor\CommentsController as DoctorCommentsController;
use App\Http\Controllers\Api\Doctor\AppointmentsController as DoctorAppointmentsController;
use App\Http\Controllers\Api\Doctor\AvailabilityController as DoctorAvailabilityController;
use App\Http\Controllers\Api\Doctor\ProfileController as DoctorProfileController;
use App\Http\Controllers\Api\Doctor\NavigationController as DoctorNavigationController;
use App\Http\Controllers\Api\SubscriptionsController;

Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::patch('auth/me', [AuthController::class, 'update']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // Locations
    Route::post('locations', [LocationController::class, 'store']);
    Route::get('patients/{patient}/locations', [LocationController::class, 'index']);
    Route::get('patients/{patient}/locations/latest', [LocationController::class, 'latest']);

    // Consultations
    Route::get('consultations', [ConsultationsController::class, 'index']);
    Route::post('consultations', [ConsultationsController::class, 'store']);
    Route::get('consultations/{consultation}', [ConsultationsController::class, 'show']);
    Route::post('consultations/{consultation}/cancel', [ConsultationsController::class, 'cancel']);

    // Appointments
    Route::get('appointments', [AppointmentsController::class, 'index']);
    Route::get('appointments/{appointment}', [AppointmentsController::class, 'show']);

    // Notifications
    Route::get('notifications', [NotificationsController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationsController::class, 'unreadCount']);
    Route::post('notifications/{notification}/read', [NotificationsController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationsController::class, 'markAllAsRead']);

    // Route calculation
    Route::get('patients/{patient}/route', [RouteController::class, 'fastest']);

    // Doctors listing and pricing
    Route::get('doctors/available', [DoctorsController::class, 'available']);
    Route::get('doctors/{doctor}/calculate-price', [DoctorsController::class, 'calculatePrice']);

    // Track doctor position (for patients)
    Route::get('consultations/{consultation}/doctor-position', [DoctorNavigationController::class, 'getDoctorPosition']);

    // Device tokens
    Route::post('device-tokens', [AuthController::class, 'registerDeviceToken']);
    Route::delete('device-tokens', [AuthController::class, 'removeDeviceToken']);

    // Subscriptions
    Route::get('subscriptions/plans', [SubscriptionsController::class, 'plans']);
    Route::get('subscriptions/plans/{plan}', [SubscriptionsController::class, 'showPlan']);
    Route::get('subscriptions/my', [SubscriptionsController::class, 'mySubscription']);
    Route::get('subscriptions/history', [SubscriptionsController::class, 'history']);
    Route::post('subscriptions/subscribe', [SubscriptionsController::class, 'subscribe']);
    Route::post('subscriptions/cancel', [SubscriptionsController::class, 'cancel']);
    Route::post('subscriptions/pause', [SubscriptionsController::class, 'pause']);
    Route::post('subscriptions/resume', [SubscriptionsController::class, 'resume']);
    Route::post('subscriptions/estimate', [SubscriptionsController::class, 'estimate']);
});

// Doctor routes
Route::middleware(['auth:sanctum', 'doctor'])->prefix('doctor')->group(function () {
    // Profile
    Route::get('profile', [DoctorProfileController::class, 'show']);
    Route::patch('profile', [DoctorProfileController::class, 'update']);

    // Consultations
    Route::get('consultations', [DoctorConsultationsController::class, 'index']);
    Route::get('consultations/pending', [DoctorConsultationsController::class, 'pending']);
    Route::get('consultations/{consultation}', [DoctorConsultationsController::class, 'show']);
    Route::post('consultations/{consultation}/accept', [DoctorConsultationsController::class, 'accept']);
    Route::post('consultations/{consultation}/reject', [DoctorConsultationsController::class, 'reject']);
    Route::post('consultations/{consultation}/close', [DoctorConsultationsController::class, 'close']);

    // Comments
    Route::get('consultations/{consultation}/comments', [DoctorCommentsController::class, 'index']);
    Route::post('consultations/{consultation}/comments', [DoctorCommentsController::class, 'store']);

    // Appointments
    Route::get('appointments', [DoctorAppointmentsController::class, 'index']);
    Route::post('appointments', [DoctorAppointmentsController::class, 'store']);
    Route::get('appointments/{appointment}', [DoctorAppointmentsController::class, 'show']);
    Route::patch('appointments/{appointment}/reschedule', [DoctorAppointmentsController::class, 'reschedule']);
    Route::post('appointments/{appointment}/cancel', [DoctorAppointmentsController::class, 'cancel']);
    Route::post('appointments/{appointment}/start', [DoctorAppointmentsController::class, 'start']);
    Route::post('appointments/{appointment}/complete', [DoctorAppointmentsController::class, 'complete']);

    // Availabilities
    Route::get('availabilities', [DoctorAvailabilityController::class, 'index']);
    Route::post('availabilities', [DoctorAvailabilityController::class, 'store']);
    Route::patch('availabilities/{availability}', [DoctorAvailabilityController::class, 'update']);
    Route::delete('availabilities/{availability}', [DoctorAvailabilityController::class, 'destroy']);

    // Navigation
    Route::post('navigation/start', [DoctorNavigationController::class, 'start']);
    Route::post('navigation/position', [DoctorNavigationController::class, 'updatePosition']);
    Route::post('navigation/refresh', [DoctorNavigationController::class, 'refreshRoute']);
    Route::post('navigation/stop', [DoctorNavigationController::class, 'stop']);
});
