<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/* |-------------------------------------------------------------------------- | Web Routes |-------------------------------------------------------------------------- */

// Public Landing Page
Route::get('/', 'PublicPortalController@index')->name('landing');
Route::get('/cari-pengadilan', 'PublicPortalController@index')->name('public.finder');
Route::get('/cek-status-perkara', 'CaseStatusController@index')->name('public.cases');
Route::get('/pengaduan', 'PublicComplaintController@create')->name('public.complaints.create');
Route::post('/pengaduan', 'PublicComplaintController@store')->name('public.complaints.store');
Route::get('/pengaduan/lacak', 'PublicComplaintController@track')->name('public.complaints.track');
Route::post('/pengaduan/lacak', 'PublicComplaintController@unlock')->name('public.complaints.unlock');
Route::get('/pengaduan/tiket/{reference}', 'PublicComplaintController@show')->name('public.complaints.show');
Route::post('/pengaduan/tiket/{reference}/tutup', 'PublicComplaintController@lock')->name('public.complaints.lock');
Route::get('/pengaduan/tiket/{reference}/bukti/{id}', 'PublicComplaintController@attachment')->name('public.complaints.attachment');
Route::post('/cek-status-perkara', 'CaseStatusController@lookup')->name('public.cases.lookup');
Route::get('/wilayah', 'PublicPortalController@regions')->middleware('throttle:120,1')->name('public.regions');
Route::get('/perkiraan-biaya', 'PublicPortalController@fees')->name('public.fees');
Route::post('/perkiraan-biaya', 'PublicPortalController@estimate')->middleware('throttle:30,1')->name('public.estimate');
Route::get('/konsultasi', 'PublicPortalController@consult')->name('public.consult');
Route::get('/konsultasi/mace/{id}', 'PublicPortalController@offline')->name('public.consult.offline');
Route::get('/konsultasi/jadwal/{id}', 'PublicPortalController@availability')->middleware('throttle:90,1')->name('public.availability');
Route::post('/konsultasi', 'GuestConsultationController@store')->middleware('throttle:10,10')->name('public.book');
Route::get('/tiket/{id}', 'GuestConsultationController@show')->name('public.ticket');
Route::get('/tiket/{id}/status', 'GuestConsultationController@state')->middleware('throttle:120,1')->name('public.ticket.state');
Route::get('/tiket/{id}/pesan', 'GuestConsultationController@messages')->middleware('throttle:120,1')->name('public.ticket.messages');
Route::post('/tiket/{id}/pesan', 'GuestConsultationController@send')->middleware('throttle:30,1')->name('public.ticket.send');
Route::post('/tiket/{id}/gabung', 'GuestConsultationController@join')->name('public.ticket.join');
Route::delete('/tiket/{id}', 'GuestConsultationController@cancel')->name('public.ticket.cancel');
Route::get('/undangan/{token}', 'GuestConsultationController@invitationPage')->middleware('throttle:60,1')->name('public.invitation');
Route::post('/undangan/{token}', 'GuestConsultationController@invitationJoin')->middleware('throttle:30,1')->name('public.invitation.join');

// Guest Identification Routes
Route::get('/welcome', 'GuestController@index')->name('guest.welcome');
Route::post('/welcome/check', 'GuestController@check')->name('guest.check');
Route::get('/welcome/register', 'GuestController@registerForm')->name('guest.register.form');
Route::post('/welcome/register', 'GuestController@register')->name('guest.register');

// Staff Login Route (Bypass AutoLogin)
Route::get('/staff-login', function () {
    Auth::logout();
    session()->flash('disable_auto_login', true);
    return redirect()->route('login');
})->name('staff.login');

// Auth Routes (Login, Register, Logout)
Auth::routes();

// Dashboard & Services (Publicly accessible but now handled by AutoLogin Guest)
Route::get('/dashboard', 'DashboardController@index')->name('dashboard');

// Protected Routes (Now accessible to Guests via AutoLogin)
Route::middleware('auth')->group(function () {
    Route::get('/pta/pengaduan', 'ComplaintManagementController@index')->name('complaints.index');
    Route::get('/pta/pengaduan/{reference}', 'ComplaintManagementController@show')->name('complaints.show');
    Route::post('/pta/pengaduan/{reference}', 'ComplaintManagementController@update')->name('complaints.update');
    Route::get('/pta/pengaduan/{reference}/bukti/{id}', 'ComplaintManagementController@attachment')->name('complaints.attachment');

    Route::post('/meetings/{id}/call-guest', 'GuestQueueController@call')->name('meetings.call-guest');
    Route::post('/meetings/{id}/retry-wa', 'GuestQueueController@retry')->name('meetings.retry-wa');
    Route::get('/operations/public-data', 'PublicDataController@index')->name('operations.public-data');
    Route::post('/operations/public-data/territories', 'PublicDataController@territory')->name('operations.territories');
    Route::post('/operations/public-data/travel-origins', 'PublicDataController@travelOrigin')->name('operations.travel-origins');
    Route::post('/operations/public-data/tariffs', 'PublicDataController@tariff')->name('operations.tariffs');

    Route::get('/admin/accounts', 'AdminAccountController@index')->name('admin.accounts.index');
    Route::get('/admin/accounts/create', 'AdminAccountController@create')->name('admin.accounts.create');
    Route::post('/admin/accounts', 'AdminAccountController@store')->name('admin.accounts.store');
    Route::get('/admin/accounts/{id}/edit', 'AdminAccountController@edit')->name('admin.accounts.edit');
    Route::put('/admin/accounts/{id}', 'AdminAccountController@update')->name('admin.accounts.update');
    Route::get('/admin/work-units', 'AdminWorkUnitController@index')->name('admin.work-units.index');
    Route::get('/admin/work-units/create', 'AdminWorkUnitController@create')->name('admin.work-units.create');
    Route::post('/admin/work-units', 'AdminWorkUnitController@store')->name('admin.work-units.store');
    Route::get('/admin/work-units/{id}/edit', 'AdminWorkUnitController@edit')->name('admin.work-units.edit');
    Route::put('/admin/work-units/{id}', 'AdminWorkUnitController@update')->name('admin.work-units.update');
    Route::get('/operations/queue', 'QueueDashboardController@index')->name('operations.queue');
    Route::get('/operations/queue/data', 'QueueDashboardController@data')->name('operations.queue.data');
    Route::get('/operations/hours', 'ServiceHoursController@index')->name('operations.hours');
    Route::put('/operations/hours', 'ServiceHoursController@update')->name('operations.hours.update');
    Route::post('/operations/holidays', 'ServiceHoursController@storeHoliday')->name('operations.holidays.store');
    Route::delete('/operations/holidays/{id}', 'ServiceHoursController@destroyHoliday')->name('operations.holidays.destroy');

    // Services
    Route::get('/services', 'ServiceController@index')->name('services.index');
    Route::post('/services', 'ServiceController@store')->name('services.store'); // Admin only logic handled inside controller/policy

    // Chat
    Route::get('/chat', 'ChatController@index')->name('chat.index');
    Route::get('/chat/voice-mode', 'ChatController@voice')->name('chat.voice');
    Route::get('/chat/{id}', 'ChatController@show')->name('chat.show');
    Route::post('/chat/send', 'ChatController@store')->name('chat.store');
    Route::post('/chat/create-room', 'ChatController@createRoom')->name('chat.createRoom');
    Route::get('/chat/{id}/fetch', 'ChatController@fetchMessages')->name('chat.fetch');

    Route::get('/meetings/availability', 'MeetingController@availability')->name('meetings.availability');

    // Google Meet
    Route::get('/meetings/manage', 'ConsultationAvailabilityController@index')->name('meetings.manage');
    Route::post('/meetings/settings', 'ConsultationAvailabilityController@settings')->name('meetings.settings');
    Route::get('/meetings', 'MeetingController@index')->name('meetings.index');
    Route::get('/meetings/create', 'MeetingController@create')->name('meetings.create');
    Route::post('/meetings', 'MeetingController@store')->name('meetings.store');
    Route::get('/meetings/{id}/join', 'MeetingController@join')->name('meetings.join');
    Route::get('/meetings/{id}/queue', 'MeetingController@queue')->name('meetings.queue');
    Route::post('/meetings/{id}/enter', 'MeetingController@enter')->name('meetings.enter');
    Route::post('/meetings/{id}/start', 'MeetingController@start')->name('meetings.start');
    Route::post('/meetings/{id}/verify', 'MeetingController@verify')->name('meetings.verify');
    Route::post('/meetings/{id}/complete', 'MeetingController@complete')->name('meetings.complete');
    Route::delete('/meetings/{id}', 'MeetingController@destroy')->name('meetings.destroy');

    Route::get('/notifications/fetch', 'NotificationController@fetch')->name('notifications.fetch');

    // Call Center
    Route::get('/call-center', 'CallCenterController@index')->name('call-center.index');
    Route::post('/call-center/call', 'CallCenterController@call')->name('call-center.call');
    Route::post('/call-center/{id}/end', 'CallCenterController@endCall')->name('call-center.end');
    Route::get('/call-center/history', 'CallCenterController@history')->name('call-center.history');

});
