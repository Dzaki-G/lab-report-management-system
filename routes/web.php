<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FormPengujianController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Enums\Role;


Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notif}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{notif}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
});

// Form List, Detail Form, & Statistik accessible by Admin and Kepala UPA
Route::middleware(['auth', 'role:' . Role::ADMIN . ',' . Role::KEPALA_UPA])->group(function () {
    // Semua Form (arsip lintas role)
    Route::get('/semua-form', [\App\Http\Controllers\FormListController::class, 'index'])
        ->name('form-list.index');
    Route::get('/semua-form/{form}', [\App\Http\Controllers\FormListController::class, 'show'])
        ->name('form-list.show');
    Route::get('/statistik', [\App\Http\Controllers\StatisticController::class, 'index'])
        ->name('statistics.index');

    // Form Pengujian — read-only list & detail (shared)
    Route::get('/form-pengujian', [FormPengujianController::class, 'index'])
        ->name('form.index');
    // IMPORTANT: /create must be registered BEFORE /{form} wildcard
    Route::get('/form-pengujian/create', [FormPengujianController::class, 'create'])
        ->name('form.create')
        ->middleware('role:' . Role::ADMIN); // Only Admin can create
    Route::get('/form-pengujian/{form}', [FormPengujianController::class, 'show'])
        ->name('form.show');
});

Route::middleware(['auth', 'role:1'])->get('/test', function () {
    return 'OK';
});

Route::middleware(['auth', 'role:' . Role::SUPER_ADMIN])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::get('/users/{user}/password', [UserController::class, 'editPassword'])->name('users.password.edit');
    Route::put('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.password.update');

    // Clear all forms (for testing)
    Route::post('/clear-forms', [\App\Http\Controllers\DashboardController::class, 'clearAllForms'])->name('admin.clear-forms');
});

Route::middleware(['auth', 'role:' . Role::ADMIN])->group(function () {
    // Units CRUD
    Route::resource('units', UnitController::class);



    Route::post('/form-pengujian', [FormPengujianController::class, 'store'])
        ->name('form.store');
    
    Route::get('/form-pengujian/{form}/samples', [SampleController::class, 'index'])
        ->name('samples.index');
    
    Route::post('/form-pengujian/{form}/samples', [SampleController::class, 'store'])
        ->name('samples.store');

    // Delete form (Admin only) - removes form + all Google Docs
    Route::delete('/form-pengujian/{form}', [FormPengujianController::class, 'destroy'])
        ->name('form.destroy');

    // Admin update SP3 SPPP & IK
    Route::post('/sp3/{sp3}/update-info', [FormPengujianController::class, 'updateSp3Info'])
        ->name('admin.update-sp3-info');

    // Admin manually set/override LHP number
    Route::post('/form-pengujian/{form}/update-lhp-number', [FormPengujianController::class, 'updateLhpNumber'])
        ->name('admin.update-lhp-number');

    // SP3 doc async status + retry
    Route::get('/sp3/{sp3}/doc-status', [FormPengujianController::class, 'sp3DocStatus'])
        ->name('admin.sp3-doc-status');
    Route::post('/sp3/{sp3}/retry-doc', [FormPengujianController::class, 'retrySp3Doc'])
        ->name('admin.sp3-retry-doc');

    // LHP ready-to-send list + mark sent
    Route::get('/admin/lhp-siap-kirim', [FormPengujianController::class, 'lhpReady'])
        ->name('admin.lhp-ready');
    Route::post('/form-pengujian/{form}/mark-sent', [FormPengujianController::class, 'markSent'])
        ->name('admin.mark-sent');



    Route::get('/form-pengujian/{form}/samples/create', [SampleController::class, 'create'])
        ->name('samples.create');

    Route::get('/samples/{sample}/edit', [SampleController::class, 'edit'])
        ->name('samples.edit');

    Route::put('/samples/{sample}', [SampleController::class, 'update'])
        ->name('samples.update');

    Route::delete('/samples/{sample}', [SampleController::class, 'destroy'])
        ->name('samples.destroy');

    // Parameter CRUD Routes
    Route::get('/parameters', [\App\Http\Controllers\ParameterController::class, 'index'])
        ->name('parameters.index');
    Route::get('/parameters/create', [\App\Http\Controllers\ParameterController::class, 'create'])
        ->name('parameters.create');
    Route::post('/parameters', [\App\Http\Controllers\ParameterController::class, 'store'])
        ->name('parameters.store');
    Route::get('/parameters/{parameter}/edit', [\App\Http\Controllers\ParameterController::class, 'edit'])
        ->name('parameters.edit');
    Route::put('/parameters/{parameter}', [\App\Http\Controllers\ParameterController::class, 'update'])
        ->name('parameters.update');
    Route::patch('/parameters/{parameter}/toggle', [\App\Http\Controllers\ParameterController::class, 'toggle'])
        ->name('parameters.toggle');
    Route::delete('/parameters/{parameter}', [\App\Http\Controllers\ParameterController::class, 'destroy'])
        ->name('parameters.destroy');


});

// Analis Routes
Route::middleware(['auth', 'role:' . Role::ANALIS])->group(function () {
    Route::get('/analis', [\App\Http\Controllers\AnalisController::class, 'dashboard'])
        ->name('analis.dashboard');
    
    // Form-level routes
    Route::get('/analis/form/{form}', [\App\Http\Controllers\AnalisController::class, 'showForm'])
        ->name('analis.form.show');
    
    // Sample parameter routes
    Route::get('/analis/input/{sampleParameter}', [\App\Http\Controllers\AnalisController::class, 'inputResult'])
        ->name('analis.input');
    Route::post('/analis/store/{sampleParameter}', [\App\Http\Controllers\AnalisController::class, 'storeResult'])
        ->name('analis.store');
    
    // SP3 and LCP routes
    Route::get('/analis/sp3/{sp3}', [\App\Http\Controllers\AnalisController::class, 'showSp3'])
        ->name('analis.sp3.show');
    Route::post('/analis/sp3/{sp3}/submit-lcp', [\App\Http\Controllers\AnalisController::class, 'submitLcpLink'])
        ->name('analis.sp3.submit-lcp');
});

// Kepala UPA Routes
Route::middleware(['auth', 'role:' . Role::KEPALA_UPA])->group(function () {
    Route::get('/kepala-upa', [\App\Http\Controllers\KepalaUpaController::class, 'dashboard'])
        ->name('kepala-upa.dashboard');
    Route::get('/kepala-upa/form/{form}', [\App\Http\Controllers\KepalaUpaController::class, 'show'])
        ->name('kepala-upa.show');
    Route::post('/kepala-upa/form/{form}/sign-lhp', [\App\Http\Controllers\KepalaUpaController::class, 'signLhp'])
        ->name('kepala-upa.sign-lhp');
});

// Kepala Divisi Routes
Route::middleware(['auth', 'role:' . Role::KEPALA_DIVISI])->group(function () {
    Route::get('/kepala-divisi', [\App\Http\Controllers\KepalaDivisiController::class, 'dashboard'])
        ->name('kepala-divisi.dashboard');
    Route::get('/kepala-divisi/form/{form}', [\App\Http\Controllers\KepalaDivisiController::class, 'show'])
        ->name('kepala-divisi.show');
    // Per-SP3 review routes
    Route::get('/kepala-divisi/form/{form}/review', [\App\Http\Controllers\KepalaDivisiController::class, 'reviewLhp'])
        ->name('kepala-divisi.review-lhp');
    Route::post('/kepala-divisi/sp3/{sp3}/approve', [\App\Http\Controllers\KepalaDivisiController::class, 'approveSp3'])
        ->name('kepala-divisi.sp3.approve');
    Route::post('/kepala-divisi/sp3/{sp3}/reject', [\App\Http\Controllers\KepalaDivisiController::class, 'rejectSp3'])
        ->name('kepala-divisi.sp3.reject');
    Route::post('/kepala-divisi/form/{form}/generate-lhp', [\App\Http\Controllers\KepalaDivisiController::class, 'generateLhp'])
        ->name('kepala-divisi.generate-lhp');
    Route::get('/kepala-divisi/form/{form}/lhp-status', [\App\Http\Controllers\KepalaDivisiController::class, 'lhpStatus'])
        ->name('kepala-divisi.lhp-status');
});

// Signature upload — available to Kepala UPA and Kepala Divisi
Route::middleware(['auth'])->group(function () {
    Route::get('/profile/signature', [\App\Http\Controllers\SignatureController::class, 'show'])
        ->name('signature.show');
    Route::post('/profile/signature', [\App\Http\Controllers\SignatureController::class, 'upload'])
        ->name('signature.upload');
    Route::delete('/profile/signature', [\App\Http\Controllers\SignatureController::class, 'destroy'])
        ->name('signature.destroy');
});

require __DIR__.'/auth.php';
