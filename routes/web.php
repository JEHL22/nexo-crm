<?php

use App\Http\Controllers\ActivationControlController;
use App\Http\Controllers\Admin\AdminCampaignController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDisabledLeadController;
use App\Http\Controllers\Admin\AdminLeadImportController;
use App\Http\Controllers\Admin\AdminPromoDocumentController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\AgreementAttachmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExecutiveActivityMonitoringController;
use App\Http\Controllers\ExecutiveActivitySessionController;
use App\Http\Controllers\ExecutivePromoDocumentController;
use App\Http\Controllers\ExecutiveTmoSessionController;
use App\Http\Controllers\HrSurveyController;
use App\Http\Controllers\InternalMessageController;
use App\Http\Controllers\MarketingPhraseController;
use App\Http\Controllers\MySalesController;
use App\Http\Controllers\MyWorkController;
use App\Http\Controllers\PostSaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionNameController;
use App\Http\Controllers\ReminderNotificationController;
use App\Http\Controllers\SupervisorAgreementController;
use App\Http\Controllers\SupervisorDashboardController;
use App\Http\Controllers\SupervisorStatusNotificationController;
use App\Http\Controllers\SupervisorTeamBaseController;
use App\Http\Controllers\TerritorialCoverageController;
use App\Http\Controllers\TmoMonitoringController;
use App\Http\Controllers\ValidationController;
use App\Http\Controllers\WorkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = request()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    if ($user->hasRole('Ejecutivo')) {
        return redirect()->route('work.show');
    }

    if ($user->hasRole('Postventa')) {
        return redirect()->route('post-sale.index');
    }

    if ($user->hasRole('Supervisor')) {
        return redirect()->route('supervisor.agreements.index');
    }

    if ($user->hasRole('Mesa de Control')) {
        return redirect()->route('validation.index');
    }

    if ($user->hasRole('Gerencia')) {
        return redirect()->route('dashboard');
    }

    if ($user->hasRole('RRHH')) {
        return redirect()->route('rrhh.surveys.index');
    }

    if ($user->hasRole('MKT')) {
        return redirect()->route('mkt.phrases.index');
    }

    if ($user->hasRole('administrador de promociones')) {
        return redirect()->route('promotion-admin.index');
    }

    if ($user->hasRole('Administrador')) {
        return redirect()->route('admin.home');
    }

    abort(403);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:Gerencia'])
    ->name('dashboard');

Route::middleware(['auth', 'role:Gerencia'])->group(function () {
    Route::get('/gerencia/acuerdos', [DashboardController::class, 'agreements'])->name('management.agreements.index');
    Route::get('/gerencia/tmo', [TmoMonitoringController::class, 'management'])->name('management.tmo.index');
    Route::get('/gerencia/tmo/pulse', [TmoMonitoringController::class, 'managementPulse'])->middleware('throttle:120,1')->name('management.tmo.pulse');
    Route::get('/gerencia/actividad-ejecutiva', [ExecutiveActivityMonitoringController::class, 'management'])->name('management.activity-monitoring.index');
    Route::get('/gerencia/actividad-ejecutiva/pulse', [ExecutiveActivityMonitoringController::class, 'managementPulse'])->middleware('throttle:120,1')->name('management.activity-monitoring.pulse');
    Route::get('/gerencia/mensajes', [InternalMessageController::class, 'managementIndex'])->name('management.internal-messages.index');
    Route::post('/gerencia/mensajes', [InternalMessageController::class, 'managementStore'])->name('management.internal-messages.store');
});

Route::middleware(['auth', 'role:RRHH'])->group(function () {
    Route::get('/rrhh/formularios', [HrSurveyController::class, 'index'])->name('rrhh.surveys.index');
    Route::get('/rrhh/formularios/feed', [HrSurveyController::class, 'feed'])->name('rrhh.surveys.feed');
    Route::post('/rrhh/formularios', [HrSurveyController::class, 'store'])->name('rrhh.surveys.store');
    Route::put('/rrhh/formularios/{survey}', [HrSurveyController::class, 'update'])->name('rrhh.surveys.update');
});

Route::middleware(['auth', 'role:MKT'])->group(function () {
    Route::get('/mkt/frases', [MarketingPhraseController::class, 'index'])->name('mkt.phrases.index');
    Route::post('/mkt/frases', [MarketingPhraseController::class, 'store'])->name('mkt.phrases.store');
    Route::put('/mkt/frases/{phrase}', [MarketingPhraseController::class, 'update'])->name('mkt.phrases.update');
    Route::post('/mkt/frases/{phrase}/publicar', [MarketingPhraseController::class, 'toggle'])->name('mkt.phrases.toggle');
});

Route::middleware(['auth', 'role:administrador de promociones'])->group(function () {
    Route::get('/administrador-promociones', [PromotionNameController::class, 'index'])->name('promotion-admin.index');
    Route::post('/administrador-promociones', [PromotionNameController::class, 'store'])->name('promotion-admin.store');
    Route::put('/administrador-promociones/{promotionName}', [PromotionNameController::class, 'update'])->name('promotion-admin.update');
    Route::delete('/administrador-promociones/{promotionName}', [PromotionNameController::class, 'destroy'])->name('promotion-admin.destroy');
    Route::get('/administrador-promociones/pdf', [AdminPromoDocumentController::class, 'index'])->name('promotion-admin.documents.index');
    Route::post('/administrador-promociones/pdf', [AdminPromoDocumentController::class, 'store'])->name('promotion-admin.documents.store');
    Route::put('/administrador-promociones/pdf/{promotion}', [AdminPromoDocumentController::class, 'update'])->name('promotion-admin.documents.update');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/mi-chamba', [MyWorkController::class, 'index'])->name('my-work.index');

    Route::get('/mi-chamba/base', [MyWorkController::class, 'base'])->name('my-work.base');
    Route::post('/mi-chamba/base', [MyWorkController::class, 'storeBase'])->name('my-work.base.store');

    Route::get('/mi-chamba/{lead}', [MyWorkController::class, 'show'])
        ->whereNumber('lead')
        ->name('my-work.show');

    Route::post('/mi-chamba/{lead}', [MyWorkController::class, 'update'])
        ->whereNumber('lead')
        ->name('my-work.update');

    Route::post('/mi-chamba/{lead}/aceptar-acuerdo', [MyWorkController::class, 'acceptAgreement'])
        ->whereNumber('lead')
        ->name('my-work.accept-agreement');
});

// Descarga autorizada de adjuntos de acuerdos: cualquier rol con acceso a la
// venta (la autorización fina vive en el controller)
Route::middleware('auth')->group(function () {
    Route::get('/acuerdos/{sale}/adjuntos/{filename}', [AgreementAttachmentController::class, 'show'])
        ->whereNumber('sale')
        ->name('agreements.attachments.show');
});

Route::middleware(['auth', 'role:Ejecutivo'])->group(function () {
    Route::get('/work', [WorkController::class, 'show'])->name('work.show');
    Route::post('/work/reminder-notifications', [ReminderNotificationController::class, 'store'])->name('work.reminder-notifications.store');
    Route::get('/work/reminder-notifications/pulse', [ReminderNotificationController::class, 'pulse'])->middleware('throttle:120,1')->name('work.reminder-notifications.pulse');
    Route::get('/work/reminder-notifications/{notification}/open', [ReminderNotificationController::class, 'open'])->name('work.reminder-notifications.open');
    Route::post('/work/reminder-notifications/{notification}/read', [ReminderNotificationController::class, 'markAsRead'])->name('work.reminder-notifications.read');
    Route::post('/work/reminder-notifications/read-all', [ReminderNotificationController::class, 'markAllAsRead'])->name('work.reminder-notifications.read-all');
    Route::post('/work/{lead}', [WorkController::class, 'store'])->whereNumber('lead')->name('work.store');
    Route::post('/work/{lead}/aceptar-acuerdo', [WorkController::class, 'acceptAgreement'])->whereNumber('lead')->name('work.accept-agreement');
    Route::post('/tmo/sessions/start', [ExecutiveTmoSessionController::class, 'start'])->name('tmo.sessions.start');
    Route::post('/tmo/sessions/heartbeat', [ExecutiveTmoSessionController::class, 'heartbeat'])->name('tmo.sessions.heartbeat');
    Route::post('/tmo/sessions/stop', [ExecutiveTmoSessionController::class, 'stop'])->name('tmo.sessions.stop');
    Route::post('/activity/sessions/ensure', [ExecutiveActivitySessionController::class, 'ensure'])->name('activity.sessions.ensure');
    Route::post('/activity/sessions/heartbeat', [ExecutiveActivitySessionController::class, 'heartbeat'])->name('activity.sessions.heartbeat');
    Route::post('/activity/sessions/event', [ExecutiveActivitySessionController::class, 'event'])->name('activity.sessions.event');
    Route::post('/activity/sessions/stop', [ExecutiveActivitySessionController::class, 'stop'])->name('activity.sessions.stop');

    Route::get('/mis-ventas', [MySalesController::class, 'index'])->name('my-sales.index');
    Route::get('/mis-promociones', [ExecutivePromoDocumentController::class, 'index'])->name('executive-promotions.index');
    Route::get('/mi-cobertura', [TerritorialCoverageController::class, 'executiveIndex'])->name('executive-coverage.index');
    Route::get('/mi-cobertura/datos', [TerritorialCoverageController::class, 'executiveData'])->name('executive-coverage.data');
});

Route::middleware(['auth', 'role:Supervisor'])->group(function () {
    Route::get('/supervisor/dashboard', [SupervisorDashboardController::class, 'index'])->name('supervisor.dashboard.index');
    Route::get('/supervisor/acuerdos', [SupervisorAgreementController::class, 'index'])->name('supervisor.agreements.index');
    Route::get('/supervisor/acuerdos/pulse', [SupervisorAgreementController::class, 'pulse'])->middleware('throttle:120,1')->name('supervisor.agreements.pulse');
    Route::get('/supervisor/acuerdos/{sale}', [SupervisorAgreementController::class, 'show'])->name('supervisor.agreements.show');
    Route::put('/supervisor/acuerdos/{sale}', [SupervisorAgreementController::class, 'update'])->name('supervisor.agreements.update');
    Route::post('/supervisor/acuerdos/{sale}/validar', [SupervisorAgreementController::class, 'validateAgreement'])->name('supervisor.agreements.validate');
    Route::get('/supervisor/mi-base', [SupervisorTeamBaseController::class, 'index'])->name('supervisor.team-base.index');
    Route::post('/supervisor/mi-base/{lead}/sisac', [SupervisorTeamBaseController::class, 'updateSisac'])->whereNumber('lead')->name('supervisor.team-base.sisac.update');
    Route::get('/supervisor/tmo', [TmoMonitoringController::class, 'supervisor'])->name('supervisor.tmo.index');
    Route::get('/supervisor/tmo/pulse', [TmoMonitoringController::class, 'supervisorPulse'])->middleware('throttle:120,1')->name('supervisor.tmo.pulse');
    Route::get('/supervisor/actividad-ejecutiva', [ExecutiveActivityMonitoringController::class, 'supervisor'])->name('supervisor.activity-monitoring.index');
    Route::get('/supervisor/actividad-ejecutiva/pulse', [ExecutiveActivityMonitoringController::class, 'supervisorPulse'])->middleware('throttle:120,1')->name('supervisor.activity-monitoring.pulse');
    Route::get('/supervisor/promociones', [ExecutivePromoDocumentController::class, 'supervisorIndex'])->name('supervisor.promotions.index');
    Route::get('/supervisor/mensajes', [InternalMessageController::class, 'supervisorIndex'])->name('supervisor.internal-messages.index');
    Route::post('/supervisor/mensajes', [InternalMessageController::class, 'supervisorStore'])->name('supervisor.internal-messages.store');
    Route::get('/supervisor/notificaciones/mesa-control/pulse', [SupervisorStatusNotificationController::class, 'pulse'])->middleware('throttle:120,1')->name('supervisor.status-notifications.pulse');
    Route::get('/supervisor/notificaciones/mesa-control/{notification}/open', [SupervisorStatusNotificationController::class, 'open'])->name('supervisor.status-notifications.open');
    Route::post('/supervisor/notificaciones/mesa-control/{notification}/read', [SupervisorStatusNotificationController::class, 'markAsRead'])->name('supervisor.status-notifications.read');
    Route::post('/supervisor/notificaciones/mesa-control/read-all', [SupervisorStatusNotificationController::class, 'markAllAsRead'])->name('supervisor.status-notifications.read-all');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/internal-messages/pulse', [InternalMessageController::class, 'pulse'])->middleware('throttle:120,1')->name('internal-messages.pulse');
    Route::post('/internal-messages/{recipient}/displayed', [InternalMessageController::class, 'markDisplayed'])->name('internal-messages.displayed');
    Route::post('/internal-messages/{recipient}/read', [InternalMessageController::class, 'markRead'])->name('internal-messages.read');
    Route::get('/rrhh/formularios/pulse', [HrSurveyController::class, 'pulse'])->middleware('throttle:120,1')->name('rrhh.surveys.pulse');
    Route::post('/rrhh/formularios/{recipient}/displayed', [HrSurveyController::class, 'markDisplayed'])->name('rrhh.surveys.displayed');
    Route::post('/rrhh/formularios/{recipient}/answer', [HrSurveyController::class, 'answer'])->name('rrhh.surveys.answer');
    Route::get('/mkt/frases/pulse', [MarketingPhraseController::class, 'pulse'])->middleware('throttle:120,1')->name('mkt.phrases.pulse');
});

Route::middleware(['auth', 'role:Postventa'])->group(function () {
    Route::get('/gestion', [PostSaleController::class, 'index'])->name('post-sale.index');
    Route::post('/gestion/{sale}', [PostSaleController::class, 'update'])->name('post-sale.update');
});

Route::middleware(['auth', 'role:Mesa de Control'])->group(function () {
    Route::get('/validacion', [ValidationController::class, 'index'])->name('validation.index');
    Route::get('/validacion/pulse', [ValidationController::class, 'pulse'])->middleware('throttle:120,1')->name('validation.pulse');
    Route::post('/validacion/{sale}', [ValidationController::class, 'update'])->name('validation.update');
    Route::get('/control-activaciones', [ActivationControlController::class, 'index'])->name('activation-control.index');
    Route::post('/control-activaciones', [ActivationControlController::class, 'store'])->name('activation-control.store');
    Route::post('/control-activaciones/exportar', [ActivationControlController::class, 'export'])->name('activation-control.export');
    Route::get('/cobertura-territorial', [TerritorialCoverageController::class, 'index'])->name('territorial-coverage.index');
    Route::get('/cobertura-territorial/datos', [TerritorialCoverageController::class, 'data'])->name('territorial-coverage.data');
    Route::post('/cobertura-territorial/provincias/{provinceId}', [TerritorialCoverageController::class, 'updateProvince'])
        ->name('territorial-coverage.provinces.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/photo/{user}', [ProfileController::class, 'photo'])->name('profile.photo.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Admin
Route::middleware(['auth', 'bootstrap.admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        if (request()->user()?->hasRole('Administrador')) {
            return redirect()->route('admin.users.index');
        }

        return redirect()->route('admin.dashboard');
    })->name('home');

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');

    Route::get('/campaigns', [AdminCampaignController::class, 'index'])->name('campaigns.index');
    Route::post('/campaigns', [AdminCampaignController::class, 'store'])->name('campaigns.store');
    Route::put('/campaigns/{campaign}', [AdminCampaignController::class, 'update'])->name('campaigns.update');

    Route::get('/promotions', [AdminPromoDocumentController::class, 'index'])->name('promotions.index');
    Route::post('/promotions', [AdminPromoDocumentController::class, 'store'])->name('promotions.store');
    Route::put('/promotions/{promotion}', [AdminPromoDocumentController::class, 'update'])->name('promotions.update');

    Route::get('/leads/import', [AdminLeadImportController::class, 'index'])->name('leads.import');
    Route::post('/leads/import/preview', [AdminLeadImportController::class, 'preview'])->name('leads.preview');
    Route::post('/leads/import', [AdminLeadImportController::class, 'store'])->name('leads.store');
    Route::get('/leads/import/template', [AdminLeadImportController::class, 'template'])->name('leads.template');

    Route::get('/disabled-leads', [AdminDisabledLeadController::class, 'index'])->name('disabled-leads.index');
    Route::post('/disabled-leads/{lead}/reactivate', [AdminDisabledLeadController::class, 'reactivate'])->name('disabled-leads.reactivate');
    Route::get('/mensajes', [InternalMessageController::class, 'adminIndex'])->name('internal-messages.index');
    Route::post('/mensajes', [InternalMessageController::class, 'adminStore'])->name('internal-messages.store');
});

require __DIR__.'/auth.php';
