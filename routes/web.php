<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FormPengujianController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\SpuController;

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
});

// Form List accessible by all roles except Super Admin
Route::middleware(['auth'])->group(function () {
    Route::get('/semua-form', [\App\Http\Controllers\FormListController::class, 'index'])
        ->name('form-list.index');
    Route::get('/semua-form/{form}', [\App\Http\Controllers\FormListController::class, 'show'])
        ->name('form-list.show');
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
    Route::get('/form-pengujian', [FormPengujianController::class, 'index'])
        ->name('form.index');
    
    // Units CRUD
    Route::resource('units', UnitController::class);

    Route::get('/form-pengujian/create', [FormPengujianController::class, 'create'])
        ->name('form.create');

    Route::post('/form-pengujian', [FormPengujianController::class, 'store'])
        ->name('form.store');
    
    Route::get('/form-pengujian/{form}/samples', [SampleController::class, 'index'])
        ->name('samples.index');
    
    Route::post('/form-pengujian/{form}/samples', [SampleController::class, 'store'])
        ->name('samples.store');
    Route::get('/form-pengujian/{form}', [FormPengujianController::class, 'show'])
        ->name('form.show');

    // Delete form (Admin only) - removes form + all Google Docs
    Route::delete('/form-pengujian/{form}', [FormPengujianController::class, 'destroy'])
        ->name('form.destroy');

    // Admin update SP3 SPPP & IK
    Route::post('/sp3/{sp3}/update-info', [FormPengujianController::class, 'updateSp3Info'])
        ->name('admin.update-sp3-info');

    // Input LHP routes (after Kepala Divisi approves hasil)
    Route::get('/input-lhp', [FormPengujianController::class, 'pendingInputLhp'])
        ->name('form.input-lhp');
    Route::post('/form-pengujian/{form}/submit-lhp', [FormPengujianController::class, 'submitLhp'])
        ->name('form.submit-lhp');
    Route::post('/form-pengujian/{form}/reject-lhp', [FormPengujianController::class, 'rejectLhp'])
        ->name('form.reject-lhp');

    // Kirim Customer routes (after Kepala UPA signs)
    Route::get('/kirim-customer', [FormPengujianController::class, 'pendingKirim'])
        ->name('form.kirim-customer');
    Route::post('/form-pengujian/{form}/konfirmasi-kirim', [FormPengujianController::class, 'konfirmasiKirim'])
        ->name('form.konfirmasi-kirim');

    // SPU Document Routes
    Route::post('/form-pengujian/{form}/generate-spu', [SpuController::class, 'generate'])
        ->name('form.generate-spu');
    Route::get('/form-pengujian/{form}/view-spu', [SpuController::class, 'view'])
        ->name('form.view-spu');
    Route::get('/form-pengujian/{form}/download-spu', [SpuController::class, 'download'])
        ->name('form.download-spu');
    Route::post('/form-pengujian/{form}/regenerate-spu', [SpuController::class, 'regenerate'])
        ->name('form.regenerate-spu');
    
    // LHP Link Input Routes (Admin)
    Route::get('/form-pengujian/{form}/input-lhp', [FormPengujianController::class, 'showInputLhp'])
        ->name('form.input-lhp.show');
    Route::post('/form-pengujian/{form}/submit-lhp-link', [FormPengujianController::class, 'submitLhpLink'])
        ->name('form.submit-lhp-link');

    Route::get('/form-pengujian/{form}/samples/create', [SampleController::class, 'create'])
        ->name('samples.create');

    Route::post('/form-pengujian/{form}/samples', [SampleController::class, 'store'])
        ->name('samples.store');

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
    Route::post('/analis/start/{sampleParameter}', [\App\Http\Controllers\AnalisController::class, 'startWork'])
        ->name('analis.start');
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
    Route::post('/kepala-upa/form/{form}/approve', [\App\Http\Controllers\KepalaUpaController::class, 'approve'])
        ->name('kepala-upa.approve');
    Route::post('/kepala-upa/form/{form}/reject', [\App\Http\Controllers\KepalaUpaController::class, 'reject'])
        ->name('kepala-upa.reject');
});

// Kepala Divisi Routes
Route::middleware(['auth', 'role:' . Role::KEPALA_DIVISI])->group(function () {
    Route::get('/kepala-divisi', [\App\Http\Controllers\KepalaDivisiController::class, 'dashboard'])
        ->name('kepala-divisi.dashboard');
    Route::get('/kepala-divisi/form/{form}', [\App\Http\Controllers\KepalaDivisiController::class, 'show'])
        ->name('kepala-divisi.show');
    Route::post('/kepala-divisi/form/{form}/approve', [\App\Http\Controllers\KepalaDivisiController::class, 'approve'])
        ->name('kepala-divisi.approve');
    
    // New Routes for SPU & SP3
    Route::post('/kepala-divisi/form/{form}/sign-spu', [\App\Http\Controllers\KepalaDivisiController::class, 'signSpu'])
        ->name('kepala-divisi.sign-spu');
    Route::post('/kepala-divisi/sp3/{sp3}/update', [\App\Http\Controllers\KepalaDivisiController::class, 'updateSp3'])
        ->name('kepala-divisi.update-sp3');
    
    // LCP Approval Route (after analis submit LCP)
    Route::post('/kepala-divisi/form/{form}/approve-lcp', [\App\Http\Controllers\KepalaDivisiController::class, 'approveLcp'])
        ->name('kepala-divisi.approve-lcp');
    
    // LHP Signature Route (after admin input LHP)
    Route::post('/kepala-divisi/form/{form}/sign-lhp', [\App\Http\Controllers\KepalaDivisiController::class, 'signLhp'])
        ->name('kepala-divisi.sign-lhp');

    Route::post('/kepala-divisi/form/{form}/assign-and-approve', [\App\Http\Controllers\KepalaDivisiController::class, 'assignAndApprove'])
        ->name('kepala-divisi.assign-and-approve');
    Route::post('/kepala-divisi/form/{form}/reject', [\App\Http\Controllers\KepalaDivisiController::class, 'reject'])
        ->name('kepala-divisi.reject');
});

require __DIR__.'/auth.php';
