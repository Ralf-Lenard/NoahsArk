<?php

use App\Http\Controllers\AdoptionAppointmentController;
use App\Http\Controllers\AdoptionRequestController;
use App\Http\Controllers\AnimalAbuseReportController;
use App\Http\Controllers\AnimalProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\VirtualMeetingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');



require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

Route::middleware(['auth'])->group(function () {
    // users
    Route::get('/home', [AdoptionRequestController::class, 'indexUser'])->name('user.home')->middleware('auth');
    Route::get('/animals/{animalProfile}', [AnimalProfileController::class, 'show'])->name('animal-profile.show');
    Route::post('/adoption', [AdoptionRequestController::class, 'store'])->name('adoption.store');

    Route::get('/report-abuse', [AnimalAbuseReportController::class, 'indexUser'])->name('index.animal-abuse');
    Route::post('/animal-abuse-report', [AnimalAbuseReportController::class, 'store'])->name('report-abuse.store');

    // Route::get('/profile', [UserProfileController::class, 'index'])->middleware('auth');
    Route::put('/profile/update', [UserProfileController::class, 'update'])->middleware('auth');

    // notificaton
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'delete']);
    Route::post('/notifications/clear-all', [NotificationController::class, 'clearAll']);
});

// chats
Route::middleware(['auth'])->group(function () {
    Route::get('/chats', [ChatController::class, 'index'])->name('user.chats');
    Route::get('/chats/contacts', [ChatController::class, 'getContacts']);
    Route::get('/chats/messages/{receiverId}', [ChatController::class, 'getMessages']);
    Route::post('/chats/send', [ChatController::class, 'sendMessage']);
    Route::post('/chats/mark-as-read/{contactId}', [ChatController::class, 'markAsRead'])->name('markAsRead');
    Route::get('/chats/unread-count', [ChatController::class, 'getUnreadCount'])->name('chats.unreadCount');
});



Route::middleware(['staff', 'auth'])->group(function () {

    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    // admin 
    // animal profile
    Route::get('/admin/animal-profiles', [AnimalProfileController::class, 'index'])->name('animal-profiles.show');
    Route::get('/admin/animals/{animalProfile}', [AnimalProfileController::class, 'showStaff'])->name('animal-profile-staff.show');
    Route::post('/animal-profiles-create', [AnimalProfileController::class, 'store'])->name('animal-profiles.store');
    Route::put('/animal-profiles-update/{id}', [AnimalProfileController::class, 'update'])->name('animal-profiles.update');
    Route::post('/animal-profiles-adopted/{id}', [AnimalProfileController::class, 'markAsAdopted'])->name('animal-profiles.mark-as-adopted');
    Route::delete('/animal-profiles-delete/{animalProfile}', [AnimalProfileController::class, 'destroy'])->name('animal-profiles.destroy');
    Route::get('/admin/animal-location/{device_id}', [AnimalProfileController::class, 'AnimalLocation'])->name('admin.animal-location');

    // adoption request
    Route::get('/admin/adoption-requests', [AdoptionRequestController::class, 'index'])->name('adoption-request.show');
    Route::post('/admin/adoption-status/{adoptionRequest}', [AdoptionRequestController::class, 'updateStatus'])->name('adoption-requests.update-status');

    // animal abuse report
    Route::get('/admin/animal-abuse-report', [AnimalAbuseReportController::class, 'index'])->name('animal-abuse-report.show');
    Route::post('/admin/abuse-status/{animalAbuseReport}', [AnimalAbuseReportController::class, 'updateStatus'])->name('animal-abuse-reports.update-status');

    // appointments
    Route::get('/admin/appointments', [AdoptionAppointmentController::class, 'index'])->name('appointments.index');
    Route::post('/admin/create-appointments', [AdoptionAppointmentController::class, 'store'])->name('appointments.store');
    Route::post('/admin/update-appointments/{appointment}', [AdoptionAppointmentController::class, 'update'])->name('appointments.update');
    Route::delete('/admin/delete-appointments/{appointment}', [AdoptionAppointmentController::class, 'destroy'])->name('appointments.destroy');

    // Admin MEETING
    Route::post("/createMeeting", [VirtualMeetingController::class, 'createMeeting'])->name("createMeeting");
    Route::post("/validateMeeting", [VirtualMeetingController::class, 'validateMeeting'])->name("validateMeeting");
    Route::get("/admin/meeting/{meetingId}", function ($meetingId) {
        $meteredDomain = env('METERED_DOMAIN');  // Make sure METERED_DOMAIN is set in .env

        return Inertia::render('Staff/VirtualMeeting', [
            'METERED_DOMAIN' => $meteredDomain,
            'MEETING_ID' => $meetingId,  // Pass the actual meeting ID here
            'authUser' => Auth::user(),
        ]);
    })->middleware('auth');

    Route::get('/admin/virtual-meeting', function () {
        return Inertia::render('Staff/CreateMeeting');
    })->middleware('auth');
});


// Users Meeting
Route::get("/virtual-meeting/{meetingId}", function ($meetingId) {
    $meteredDomain = env('METERED_DOMAIN');  // Make sure METERED_DOMAIN is set in .env

    return Inertia::render('Users/VirtualMeeting', [
        'METERED_DOMAIN' => $meteredDomain,
        'MEETING_ID' => $meetingId,  // Pass the actual meeting ID here
        'authId' => Auth::id(),
    ]);
})->middleware('auth');

Route::get('/virtual-meeting', function () {
    return Inertia::render('Users/JoinMeeting');
})->middleware('auth');



Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
