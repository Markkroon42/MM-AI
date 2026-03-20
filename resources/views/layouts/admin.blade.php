<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - Meta AI Marketing Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/campaign-builder.css') }}">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #212529;
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            font-weight: 500;
            color: #adb5bd;
            padding: 0.75rem 1rem;
        }
        .sidebar .nav-link:hover {
            color: #ffffff;
            background-color: #343a40;
        }
        .sidebar .nav-link.active {
            color: #ffffff;
            background-color: #0d6efd;
        }
        .sidebar .nav-link i {
            margin-right: 8px;
        }
        .sidebar-heading {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        main {
            margin-left: 240px;
            padding-top: 48px;
        }
        .navbar {
            padding-left: 240px;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: none;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Top Navbar -->
    <nav class="navbar navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
                <i class="bi bi-graph-up-arrow"></i> Meta Marketing Platform
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="navbar-text text-light">
                    {{ auth()->user()->name ?? 'Admin' }}
                </span>
                <x-language-switcher />
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">{{ __('common.logout') }}</button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <nav class="col-md-2 d-md-block sidebar">
        <div class="sidebar-sticky">
            <ul class="nav flex-column">
                <!-- Dashboard & Operations -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                       href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-speedometer2"></i> {{ __('menu.dashboard') }}
                    </a>
                </li>
                @can('view_kpi_cockpit')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.kpi-cockpit.*') ? 'active' : '' }}"
                       href="{{ route('admin.kpi-cockpit.index') }}">
                        <i class="bi bi-graph-up"></i> {{ __('menu.kpi_cockpit') }}
                    </a>
                </li>
                @endcan

                <!-- Meta Data -->
                <li class="nav-item mt-3">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>{{ __('menu.meta_data') }}</span>
                    </h6>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.meta-accounts.*') ? 'active' : '' }}"
                       href="{{ route('admin.meta-accounts.index') }}">
                        <i class="bi bi-wallet2"></i> {{ __('menu.ad_accounts') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.campaigns.*') ? 'active' : '' }}"
                       href="{{ route('admin.campaigns.index') }}">
                        <i class="bi bi-megaphone"></i> {{ __('menu.campaigns') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.sync-runs.*') ? 'active' : '' }}"
                       href="{{ route('admin.sync-runs.index') }}">
                        <i class="bi bi-arrow-repeat"></i> {{ __('menu.sync_runs') }}
                    </a>
                </li>

                <!-- Recommendations & Execution -->
                <li class="nav-item mt-3">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>{{ __('menu.recommendations_section') }}</span>
                    </h6>
                </li>
                @can('view_recommendations')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.recommendations.*') ? 'active' : '' }}"
                       href="{{ route('admin.recommendations.index') }}">
                        <i class="bi bi-lightbulb"></i> {{ __('menu.recommendations') }}
                    </a>
                </li>
                @endcan
                @can('view_approvals')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.approvals.*') ? 'active' : '' }}"
                       href="{{ route('admin.approvals.index') }}">
                        <i class="bi bi-clipboard-check"></i> {{ __('menu.approvals') }}
                    </a>
                </li>
                @endcan

                <!-- Campaign Building -->
                <li class="nav-item mt-3">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>{{ __('menu.campaign_management') }}</span>
                    </h6>
                </li>
                @can('view_campaign_briefings')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.campaign-briefings.*') ? 'active' : '' }}"
                       href="{{ route('admin.campaign-briefings.index') }}">
                        <i class="bi bi-file-text"></i> {{ __('menu.briefings') }}
                    </a>
                </li>
                @endcan
                @can('view_campaign_drafts')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.campaign-drafts.*') ? 'active' : '' }}"
                       href="{{ route('admin.campaign-drafts.index') }}">
                        <i class="bi bi-file-earmark"></i> {{ __('menu.drafts') }}
                    </a>
                </li>
                @endcan
                @can('view_campaign_templates')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.campaign-templates.*') ? 'active' : '' }}"
                       href="{{ route('admin.campaign-templates.index') }}">
                        <i class="bi bi-layout-text-window"></i> {{ __('menu.templates') }}
                    </a>
                </li>
                @endcan
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.utm-templates.*') ? 'active' : '' }}"
                       href="{{ route('admin.utm-templates.index') }}">
                        <i class="bi bi-link-45deg"></i> {{ __('menu.utm_templates') }}
                    </a>
                </li>
                @can('view_publish_jobs')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.publish-jobs.*') ? 'active' : '' }}"
                       href="{{ route('admin.publish-jobs.index') }}">
                        <i class="bi bi-send"></i> {{ __('menu.publish_jobs') }}
                    </a>
                </li>
                @endcan

                <!-- AI & Content -->
                <li class="nav-item mt-3">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>{{ __('menu.ai_configuration') }}</span>
                    </h6>
                </li>
                @can('view_ai_prompt_configs')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.ai-prompt-configs.*') ? 'active' : '' }}"
                       href="{{ route('admin.ai-prompt-configs.index') }}">
                        <i class="bi bi-chat-square-text"></i> {{ __('menu.prompt_configs') }}
                    </a>
                </li>
                @endcan
                @can('view_ai_usage_logs')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.ai-usage-logs.*') ? 'active' : '' }}"
                       href="{{ route('admin.ai-usage-logs.index') }}">
                        <i class="bi bi-clock-history"></i> {{ __('menu.usage_logs') }}
                    </a>
                </li>
                @endcan

                <!-- Operations & Monitoring -->
                <li class="nav-item mt-3">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>{{ __('menu.orchestration_reporting') }}</span>
                    </h6>
                </li>
                @can('view_scheduled_tasks')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.scheduled-tasks.*') ? 'active' : '' }}"
                       href="{{ route('admin.scheduled-tasks.index') }}">
                        <i class="bi bi-calendar-check"></i> {{ __('menu.scheduled_tasks') }}
                    </a>
                </li>
                @endcan
                @can('view_scheduled_task_runs')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.scheduled-task-runs.*') ? 'active' : '' }}"
                       href="{{ route('admin.scheduled-task-runs.index') }}">
                        <i class="bi bi-clock-history"></i> Task Run History
                    </a>
                </li>
                @endcan
                @can('view_system_alerts')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.system-alerts.*') ? 'active' : '' }}"
                       href="{{ route('admin.system-alerts.index') }}">
                        <i class="bi bi-exclamation-triangle"></i> {{ __('menu.system_alerts') }}
                    </a>
                </li>
                @endcan
                @can('view_guardrail_rules')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.guardrail-rules.*') ? 'active' : '' }}"
                       href="{{ route('admin.guardrail-rules.index') }}">
                        <i class="bi bi-shield-check"></i> {{ __('menu.guardrail_rules') }}
                    </a>
                </li>
                @endcan

                <!-- Reports -->
                @can('view_executive_reports')
                <li class="nav-item mt-3">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>{{ __('menu.executive_reports') }}</span>
                    </h6>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.executive-reports.*') ? 'active' : '' }}"
                       href="{{ route('admin.executive-reports.index') }}">
                        <i class="bi bi-file-bar-graph"></i> {{ __('menu.executive_reports') }}
                    </a>
                </li>
                @endcan

                <!-- Settings -->
                @if(auth()->user()->can('manage_system_settings') || auth()->user()->can('view_users'))
                <li class="nav-item mt-3">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>{{ __('menu.settings') }}</span>
                    </h6>
                </li>
                @endif
                @can('view_users')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                       href="{{ route('admin.users.index') }}">
                        <i class="bi bi-people"></i> {{ __('menu.user_management') }}
                    </a>
                </li>
                @endcan
                @can('manage_system_settings')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.system-settings.*') ? 'active' : '' }}"
                       href="{{ route('admin.system-settings.index') }}">
                        <i class="bi bi-gear"></i> {{ __('menu.system_settings') }}
                    </a>
                </li>
                @endcan
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="col-md-10 ms-sm-auto px-md-4">
        <div class="container-fluid py-4">
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')

    <style>
        /* Fix pagination arrows */
        .pagination {
            margin-bottom: 0;
        }
        .page-link {
            color: #0d6efd;
        }
        .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .page-item.disabled .page-link {
            color: #6c757d;
        }
    </style>
</body>
</html>
