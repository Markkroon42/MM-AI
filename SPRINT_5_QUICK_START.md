# Sprint 5 Quick Start Guide

## What's New in Sprint 5

Sprint 5 adds **Orchestration, Guardrails, Executive Reporting, and KPI Cockpit** to your Meta AI Marketing Platform.

### Key Features:
- ⏰ **Scheduled Tasks** - Automated agent runs, reports, and syncs
- 🛡️ **Guardrails** - Safety constraints to prevent risky actions
- 📊 **Executive Reports** - Daily/weekly management summaries
- 📈 **KPI Cockpit** - Real-time operational dashboard
- 🚨 **System Alerts** - Smart operational alerts with deduplication

## Quick Start

### 1. Verify Installation

All migrations and seeders have been run successfully. Verify:

```bash
# Check tables exist
php artisan tinker
>>> \App\Models\ScheduledTask::count();
>>> \App\Models\GuardrailRule::count();
>>> exit
```

### 2. Access the KPI Cockpit

1. Login as admin (`admin@example.com` / `password`)
   2. Navigate to: `http://localhost:8000/admin/kpi-cockpit`
3. See your operational dashboard

**Note:** KPI data will be empty until you create your first snapshot.

### 3. Create Your First KPI Snapshot

```bash
php artisan kpi:snapshot
```

This creates a daily snapshot of all system metrics. Run this at the end of each day (or set up a scheduled task).

### 4. Generate Your First Executive Report

```bash
# Generate yesterday's report
php artisan reports:generate-daily-executive

# Or specify a date
php artisan reports:generate-daily-executive --date=2026-03-18
```

View the report at: `http://localhost:8000/admin/executive-reports`

### 5. View Scheduled Tasks

1. Navigate to: `http://localhost:8000/admin/scheduled-tasks`
2. See 6 pre-configured tasks:
   - Daily Performance Agent (8 AM)
   - Weekly Structure Agent (Monday 9 AM)
   - Morning Executive Report (9 AM)
   - Weekly Performance Report (Monday 10 AM)
   - Nightly KPI Snapshot (11:59 PM)
   - Hourly Meta Sync

### 6. Run a Task Manually

1. Go to Scheduled Tasks
2. Click on "Daily Performance Agent"
3. Click "Run Now" button
4. View execution results in the Runs tab

### 7. View Guardrail Rules

Navigate to: `http://localhost:8000/admin/guardrail-rules`

See 7 pre-configured safety rules:
- Block budget increases >20%
- Require approval for 10-20% increases
- Block campaign pause with <€100 spend
- And more...

### 8. Test Guardrails

Try executing a recommendation with low confidence:
- Guardrails will check before execution
- Blocked actions log to audit trail
- Warnings appear but allow action to proceed

## Setting Up Automated Orchestration

### Option 1: Laravel Scheduler (Recommended)

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Run due tasks every minute
    $schedule->command('orchestration:run-due-tasks')->everyMinute();

    // Or be more specific:
    // $schedule->command('reports:generate-daily-executive')->dailyAt('09:00');
    // $schedule->command('kpi:snapshot')->dailyAt('23:59');
}
```

Then add to your crontab:
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Option 2: Direct Cron Jobs

Add to crontab:
```
* * * * * cd /path-to-your-project && php artisan orchestration:run-due-tasks
```

## Common Operations

### Create a New Scheduled Task

1. Navigate to Scheduled Tasks
2. Click "Create Task"
3. Fill in:
   - Name: "Daily Budget Check"
   - Type: run_agent
   - Cron: `0 10 * * *` (10 AM daily)
   - Context: `{"agent_type":"performance","scope_type":"all"}`
4. Save

### Create a Custom Guardrail Rule

1. Navigate to Guardrail Rules
2. Click "Create Rule"
3. Example - Warn on weekend publishes:
   - Name: "Warn Weekend Publishes"
   - Action Type: campaign_publish
   - Condition: `is_weekend == true`
   - Effect: warn
   - Message: "Publishing on weekends may have lower performance"
   - Priority: 50

### View System Alerts

Navigate to: `http://localhost:8000/admin/system-alerts`

- Filter by status (open/acknowledged/resolved)
- Filter by severity (critical/high/medium/low)
- Acknowledge alerts to track progress
- Resolve when issue is fixed

### Generate Ad-Hoc Reports

From Executive Reports page, use "Generate Now" buttons:
- Daily Summary (yesterday's performance)
- Weekly Performance (last 7 days)

## Understanding the Data Flow

### Daily Operations Flow:
```
1. Morning (9 AM):
   - Daily Performance Agent runs → Generates recommendations
   - Executive Report generated → Email to management

2. Throughout Day:
   - Hourly Meta sync updates campaign data
   - Marketers review recommendations
   - Guardrails check all executions
   - System alerts created as needed

3. Evening (11:59 PM):
   - KPI Snapshot captures day's metrics
   - Ready for next morning's report
```

### Guardrail Flow:
```
Action Triggered (e.g., increase budget 50%)
    ↓
Build Context (current budget, spend history, etc.)
    ↓
Evaluate Rules (in priority order)
    ↓
Decision: block/require_approval/warn/allow
    ↓
Log Decision to Audit Trail
    ↓
Execute or Block Action
```

## Monitoring & Maintenance

### Health Checks

Check system health daily:
1. Visit KPI Cockpit
2. Look for critical alerts
3. Check scheduled task health status
4. Review open recommendations count

### Alert Management

Best practices:
- Acknowledge alerts when you start investigating
- Resolve alerts when issue is fixed
- Don't dismiss critical alerts without resolution
- Alerts auto-resolve when underlying issue clears

### Guardrail Tuning

Monitor guardrail effectiveness:
1. Review blocked actions in audit logs
2. Adjust thresholds in `config/guardrails.php`
3. Update rule conditions for better accuracy
4. Disable rules that create false positives

## Troubleshooting

### No KPI Data Showing

**Problem:** KPI Cockpit is empty

**Solution:**
```bash
# Create today's snapshot
php artisan kpi:snapshot

# Create yesterday's snapshot
php artisan kpi:snapshot --date=$(date -v-1d +%Y-%m-%d)
```

### Tasks Not Running Automatically

**Problem:** Scheduled tasks show as "due" but never run

**Solution:**
1. Check Laravel scheduler is running: `php artisan schedule:run`
2. Verify crontab entry exists
3. Check task status is "active"
4. Run manually: `php artisan orchestration:run-due-tasks`

### Guardrails Not Firing

**Problem:** Actions executing without guardrail checks

**Solution:**
1. Check `GUARDRAILS_ENABLED=true` in `.env`
2. Verify rules are active: `is_active=true`
3. Check action type matches: `budget_increase` vs `recommendation_execution`
4. Review rule conditions for syntax errors

### Reports Empty

**Problem:** Executive reports have no data

**Solution:**
1. Ensure Meta sync has run successfully
2. Check insights data exists: `php artisan tinker >>> \App\Models\MetaInsightDaily::count();`
3. Verify date range includes data
4. Run report for a date with known activity

## Security & Permissions

### Role Access:

**Admin:**
- Full access to all Sprint 5 features
- Can manage scheduled tasks and guardrails
- Can resolve any alert

**Marketer:**
- View KPI Cockpit and reports
- View scheduled tasks (cannot edit)
- View guardrail rules (cannot edit)
- Can acknowledge and resolve alerts
- Can generate reports on demand

**Viewer:**
- View KPI Cockpit
- View executive reports
- Read-only access

## Performance Tips

### KPI Snapshots
- Run once daily (end of day recommended)
- Retention: 365 days (configurable)
- Consider archiving old snapshots to separate table

### Executive Reports
- Generate overnight (low traffic time)
- Cache report data for quick display
- Retention: 90 days (configurable)

### System Alerts
- Auto-resolve when possible
- Archive resolved alerts after 30 days
- Monitor alert volume to detect issues

## Next Steps

1. **Customize Scheduled Tasks:** Adjust timing and context for your needs
2. **Fine-tune Guardrails:** Update thresholds based on your risk tolerance
3. **Create Custom Alerts:** Add business-specific alert logic
4. **Build Remaining Views:** Executive report show page, task CRUD forms
5. **Add Notifications:** Email/Slack integration for critical alerts
6. **Export Reports:** PDF/Excel generation for executive distribution

## Support & Documentation

- Full implementation details: See `SPRINT_5_IMPLEMENTATION.md`
- Config reference: Check `config/guardrails.php` and `config/reporting.php`
- Model docs: All models have PHPDoc with relationships
- Service docs: All services have method-level documentation

## Quick Reference Commands

```bash
# Orchestration
php artisan orchestration:run-due-tasks

# Reporting
php artisan reports:generate-daily-executive
php artisan reports:generate-weekly-performance
php artisan kpi:snapshot

# Database
php artisan migrate:fresh --seed  # Reset everything
php artisan db:seed --class=GuardrailRulesSeeder  # Reseed rules
php artisan db:seed --class=ScheduledTasksSeeder  # Reseed tasks
```

## URLs Quick Reference

- KPI Cockpit: `/admin/kpi-cockpit`
- Executive Reports: `/admin/executive-reports`
- Scheduled Tasks: `/admin/scheduled-tasks`
- Guardrail Rules: `/admin/guardrail-rules`
- System Alerts: `/admin/system-alerts`
- Dashboard: `/admin` (updated with Sprint 5 widgets)

---

**Sprint 5 is now live!** Your platform has evolved from reactive to proactive with automated orchestration, safety guardrails, and executive-level visibility. 🚀
