<?php

use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\CampaignBriefingController;
use App\Http\Controllers\Admin\CampaignDraftController;
use App\Http\Controllers\Admin\CampaignRecommendationReviewController;
use App\Http\Controllers\Admin\CampaignTemplateController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExecutiveReportController;
use App\Http\Controllers\Admin\GuardrailRuleController;
use App\Http\Controllers\Admin\KpiCockpitController;
use App\Http\Controllers\Admin\MetaAccountController;
use App\Http\Controllers\Admin\MetaCampaignController;
use App\Http\Controllers\Admin\PublishJobController;
use App\Http\Controllers\Admin\RecommendationController;
use App\Http\Controllers\Admin\ScheduledTaskController;
use App\Http\Controllers\Admin\ScheduledTaskRunController;
use App\Http\Controllers\Admin\SyncRunController;
use App\Http\Controllers\Admin\SystemAlertController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UtmTemplateController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Language switcher
Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Meta Ad Accounts
    Route::get('/meta-accounts', [MetaAccountController::class, 'index'])->name('meta-accounts.index');
    Route::get('/meta-accounts/{account}', [MetaAccountController::class, 'show'])->name('meta-accounts.show');
    Route::post('/meta-accounts/{account}/sync-campaigns', [MetaAccountController::class, 'syncCampaigns'])->name('meta-accounts.sync-campaigns');

    // Campaigns
    Route::get('/campaigns', [MetaCampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/{campaign}', [MetaCampaignController::class, 'show'])->name('campaigns.show');

    // Sync Runs
    Route::get('/sync-runs', [SyncRunController::class, 'index'])->name('sync-runs.index');

    // Recommendations
    Route::get('/recommendations', [RecommendationController::class, 'index'])->name('recommendations.index');
    Route::get('/recommendations/{recommendation}', [RecommendationController::class, 'show'])->name('recommendations.show');
    Route::post('/recommendations/{recommendation}/reviewing', [CampaignRecommendationReviewController::class, 'markReviewing'])->name('recommendations.reviewing');
    Route::post('/recommendations/{recommendation}/approve', [CampaignRecommendationReviewController::class, 'approve'])->name('recommendations.approve');
    Route::post('/recommendations/{recommendation}/reject', [CampaignRecommendationReviewController::class, 'reject'])->name('recommendations.reject');

    // Campaign Briefings
    Route::get('/campaign-briefings', [CampaignBriefingController::class, 'index'])->name('campaign-briefings.index');
    Route::get('/campaign-briefings/create', [CampaignBriefingController::class, 'create'])->name('campaign-briefings.create');
    Route::post('/campaign-briefings', [CampaignBriefingController::class, 'store'])->name('campaign-briefings.store');
    Route::get('/campaign-briefings/{briefing}', [CampaignBriefingController::class, 'show'])->name('campaign-briefings.show');
    Route::post('/campaign-briefings/{briefing}/generate-draft', [CampaignBriefingController::class, 'generateDraft'])->name('campaign-briefings.generate-draft');

    // Campaign Templates
    Route::get('/campaign-templates', [CampaignTemplateController::class, 'index'])->name('campaign-templates.index');
    Route::get('/campaign-templates/create', [CampaignTemplateController::class, 'create'])->name('campaign-templates.create');
    Route::post('/campaign-templates', [CampaignTemplateController::class, 'store'])->name('campaign-templates.store');
    Route::get('/campaign-templates/{template}', [CampaignTemplateController::class, 'show'])->name('campaign-templates.show');
    Route::get('/campaign-templates/{template}/edit', [CampaignTemplateController::class, 'edit'])->name('campaign-templates.edit');
    Route::put('/campaign-templates/{template}', [CampaignTemplateController::class, 'update'])->name('campaign-templates.update');

    // Campaign Drafts
    Route::get('/campaign-drafts', [CampaignDraftController::class, 'index'])->name('campaign-drafts.index');
    Route::get('/campaign-drafts/{draft}', [CampaignDraftController::class, 'show'])->name('campaign-drafts.show');
    Route::post('/campaign-drafts/{draft}/request-review', [CampaignDraftController::class, 'requestReview'])->name('campaign-drafts.request-review');
    Route::post('/campaign-drafts/{draft}/request-approval', [CampaignDraftController::class, 'requestApproval'])->name('campaign-drafts.request-approval');
    Route::post('/campaign-drafts/{draft}/publish', [CampaignDraftController::class, 'publish'])->name('campaign-drafts.publish');

    // Approvals
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::get('/approvals/{approval}', [ApprovalController::class, 'show'])->name('approvals.show');
    Route::post('/approvals/{approval}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{approval}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');

    // Publish Jobs
    Route::get('/publish-jobs', [PublishJobController::class, 'index'])->name('publish-jobs.index');
    Route::get('/publish-jobs/{job}', [PublishJobController::class, 'show'])->name('publish-jobs.show');
    Route::post('/publish-jobs/{job}/retry', [PublishJobController::class, 'retry'])->name('publish-jobs.retry');

    // UTM Templates
    Route::get('/utm-templates', [UtmTemplateController::class, 'index'])->name('utm-templates.index');
    Route::get('/utm-templates/create', [UtmTemplateController::class, 'create'])->name('utm-templates.create');
    Route::post('/utm-templates', [UtmTemplateController::class, 'store'])->name('utm-templates.store');

    // AI Prompt Configs
    Route::get('/ai-prompt-configs', [\App\Http\Controllers\Admin\AiPromptConfigController::class, 'index'])->name('ai-prompt-configs.index');
    Route::get('/ai-prompt-configs/create', [\App\Http\Controllers\Admin\AiPromptConfigController::class, 'create'])->name('ai-prompt-configs.create');
    Route::post('/ai-prompt-configs', [\App\Http\Controllers\Admin\AiPromptConfigController::class, 'store'])->name('ai-prompt-configs.store');
    Route::get('/ai-prompt-configs/{config}', [\App\Http\Controllers\Admin\AiPromptConfigController::class, 'show'])->name('ai-prompt-configs.show');
    Route::get('/ai-prompt-configs/{config}/edit', [\App\Http\Controllers\Admin\AiPromptConfigController::class, 'edit'])->name('ai-prompt-configs.edit');
    Route::put('/ai-prompt-configs/{config}', [\App\Http\Controllers\Admin\AiPromptConfigController::class, 'update'])->name('ai-prompt-configs.update');

    // AI Usage Logs
    Route::get('/ai-usage-logs', [\App\Http\Controllers\Admin\AiUsageLogController::class, 'index'])->name('ai-usage-logs.index');
    Route::get('/ai-usage-logs/{log}', [\App\Http\Controllers\Admin\AiUsageLogController::class, 'show'])->name('ai-usage-logs.show');

    // Campaign Briefing AI
    Route::post('/campaign-briefings/{briefing}/ai/generate-strategy', [\App\Http\Controllers\Admin\CampaignBriefingAiController::class, 'generateStrategy'])->name('campaign-briefings.ai.generate-strategy');
    Route::post('/campaign-briefings/{briefing}/ai/generate-copy', [\App\Http\Controllers\Admin\CampaignBriefingAiController::class, 'generateCopy'])->name('campaign-briefings.ai.generate-copy');
    Route::post('/campaign-briefings/{briefing}/ai/generate-creative', [\App\Http\Controllers\Admin\CampaignBriefingAiController::class, 'generateCreative'])->name('campaign-briefings.ai.generate-creative');

    // Campaign Draft AI
    Route::post('/campaign-drafts/{draft}/ai/generate-copy', [\App\Http\Controllers\Admin\CampaignDraftAiController::class, 'generateCopy'])->name('campaign-drafts.ai.generate-copy');
    Route::post('/campaign-drafts/{draft}/ai/generate-creative', [\App\Http\Controllers\Admin\CampaignDraftAiController::class, 'generateCreative'])->name('campaign-drafts.ai.generate-creative');
    Route::post('/campaign-drafts/{draft}/ai/generate-full', [\App\Http\Controllers\Admin\CampaignDraftAiController::class, 'generateFull'])->name('campaign-drafts.ai.generate-full');

    // Draft Enrichments
    Route::post('/draft-enrichments/{enrichment}/approve', [\App\Http\Controllers\Admin\DraftEnrichmentController::class, 'approve'])->name('draft-enrichments.approve');
    Route::post('/draft-enrichments/{enrichment}/reject', [\App\Http\Controllers\Admin\DraftEnrichmentController::class, 'reject'])->name('draft-enrichments.reject');
    Route::post('/draft-enrichments/{enrichment}/apply', [\App\Http\Controllers\Admin\DraftEnrichmentController::class, 'apply'])->name('draft-enrichments.apply');

    // Sprint 5: KPI Cockpit
    Route::get('/kpi-cockpit', [KpiCockpitController::class, 'index'])->name('kpi-cockpit.index');

    // Sprint 5: Executive Reports
    Route::get('/executive-reports', [ExecutiveReportController::class, 'index'])->name('executive-reports.index');
    Route::get('/executive-reports/{report}', [ExecutiveReportController::class, 'show'])->name('executive-reports.show');
    Route::post('/executive-reports/generate-daily', [ExecutiveReportController::class, 'generateDaily'])->name('executive-reports.generate-daily');
    Route::post('/executive-reports/generate-weekly', [ExecutiveReportController::class, 'generateWeekly'])->name('executive-reports.generate-weekly');

    // Sprint 5: Scheduled Tasks
    Route::get('/scheduled-tasks', [ScheduledTaskController::class, 'index'])->name('scheduled-tasks.index');
    Route::get('/scheduled-tasks/create', [ScheduledTaskController::class, 'create'])->name('scheduled-tasks.create');
    Route::post('/scheduled-tasks', [ScheduledTaskController::class, 'store'])->name('scheduled-tasks.store');
    Route::get('/scheduled-tasks/{task}', [ScheduledTaskController::class, 'show'])->name('scheduled-tasks.show');
    Route::get('/scheduled-tasks/{task}/edit', [ScheduledTaskController::class, 'edit'])->name('scheduled-tasks.edit');
    Route::put('/scheduled-tasks/{task}', [ScheduledTaskController::class, 'update'])->name('scheduled-tasks.update');
    Route::post('/scheduled-tasks/{task}/run-now', [ScheduledTaskController::class, 'runNow'])->name('scheduled-tasks.run-now');
    Route::post('/scheduled-tasks/{task}/pause', [ScheduledTaskController::class, 'pause'])->name('scheduled-tasks.pause');
    Route::post('/scheduled-tasks/{task}/resenvume', [ScheduledTaskController::class, 'resume'])->name('scheduled-tasks.resume');

    // Sprint 5: Scheduled Task Runs
    Route::get('/scheduled-task-runs', [ScheduledTaskRunController::class, 'index'])->name('scheduled-task-runs.index');
    Route::get('/scheduled-task-runs/{run}', [ScheduledTaskRunController::class, 'show'])->name('scheduled-task-runs.show');

    // Sprint 5: Guardrail Rules
    Route::get('/guardrail-rules', [GuardrailRuleController::class, 'index'])->name('guardrail-rules.index');
    Route::get('/guardrail-rules/create', [GuardrailRuleController::class, 'create'])->name('guardrail-rules.create');
    Route::post('/guardrail-rules', [GuardrailRuleController::class, 'store'])->name('guardrail-rules.store');
    Route::get('/guardrail-rules/{rule}', [GuardrailRuleController::class, 'show'])->name('guardrail-rules.show');
    Route::get('/guardrail-rules/{rule}/edit', [GuardrailRuleController::class, 'edit'])->name('guardrail-rules.edit');
    Route::put('/guardrail-rules/{rule}', [GuardrailRuleController::class, 'update'])->name('guardrail-rules.update');
    Route::delete('/guardrail-rules/{rule}', [GuardrailRuleController::class, 'destroy'])->name('guardrail-rules.destroy');

    // Sprint 5: System Alerts
    Route::get('/system-alerts', [SystemAlertController::class, 'index'])->name('system-alerts.index');
    Route::get('/system-alerts/{alert}', [SystemAlertController::class, 'show'])->name('system-alerts.show');
    Route::post('/system-alerts/{alert}/acknowledge', [SystemAlertController::class, 'acknowledge'])->name('system-alerts.acknowledge');
    Route::post('/system-alerts/{alert}/resolve', [SystemAlertController::class, 'resolve'])->name('system-alerts.resolve');

    // System Settings
    Route::get('/system-settings', [SystemSettingsController::class, 'index'])->name('system-settings.index');
    Route::patch('/system-settings/{systemSetting}', [SystemSettingsController::class, 'update'])->name('system-settings.update');

    // User Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::patch('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.update-password');
});
