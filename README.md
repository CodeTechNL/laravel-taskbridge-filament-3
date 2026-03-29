# taskbridge-filament-3

Filament v3 admin panel integration for [laravel-taskbridge](../laravel-taskbridge/README.md). Provides a complete UI for managing scheduled jobs, viewing run history, and triggering manual executions — all without touching AWS directly.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Filament 3.2+
- `codetechnl/laravel-taskbridge` (installed and configured)

## Installation

```bash
composer require codetechnl/laravel-taskbridge-filament-3
```

## Register the plugin

Add the plugin to your Filament panel provider:

```php
use CodeTechNL\TaskBridgeFilament\TaskBridgePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            TaskBridgePlugin::make(),
        ]);
}
```

That's it. The plugin automatically registers the **Scheduled Jobs** resource, the **Run Logs** resource, and the **TaskBridge stats widget**.

## What you get

### Scheduled Jobs resource

A full CRUD interface for your registered jobs:

- **Create** — select a job class from your registered jobs, configure queue connection, cron expression, retry policy, description, and enable/disable
- **Edit** — update any settings; saved changes auto-sync to EventBridge
- **View** — detailed job info with inline run history
- **Filters** — filter by group, enabled state, last status

**Row actions available on every job:**

| Action | Description |
|--------|-------------|
| Run now | Immediately executes the job (bypasses enabled / shouldRun) |
| Dry run | Calls handle() with Bus::fake() — no real queue dispatches |
| Edit | Edit job settings |
| Delete | Remove from database and EventBridge |

**Bulk actions:** Enable selected, Disable selected, Delete selected

**Header actions:**

| Action | Description |
|--------|-------------|
| Sync | Push all enabled jobs to AWS EventBridge Scheduler |
| Validate | Check that all registered job classes exist and can be loaded |

### Run Logs resource

A read-only audit log of every job execution. Columns include status, trigger type, duration, jobs dispatched, and structured output.

Filters: job name, identifier, status, trigger type.

Row action: **Output** — opens a modal with the full structured output when a job reported metadata.

### Dashboard widget

A stats overview widget showing:
- Total jobs
- Active (enabled) jobs
- Disabled jobs
- Failed runs in the last 24 hours (shown in red when > 0)

## Plugin configuration

All options are set fluently on `TaskBridgePlugin::make()`:

```php
TaskBridgePlugin::make()
    ->navigationGroup('Infrastructure')
    ->navigationLabel('Scheduler')
    ->navigationIcon('heroicon-o-clock')
    ->navigationSort(50)
    ->slug('scheduler')
    ->heading('Scheduled Jobs')
    ->subheading('Manage your AWS EventBridge schedules')
    ->paginationPageOptions([10, 25, 50])
    ->defaultPaginationPageOption(25)
    ->groupActions()          // collapse row actions into a dropdown
    ->preventDuplicates(true) // block the same class being registered twice
    ->withoutWidget()         // remove the stats widget
    ->withoutRunLog()         // remove the Run Logs page
    ->runLogNavigationLabel('Job History')
    ->runLogSlug('job-history')
    ->runLogPaginationPageOptions([25, 50])
```

### All available options

| Method | Default | Description |
|--------|---------|-------------|
| `navigationGroup(string)` | `'System'` | Sidebar group label |
| `navigationLabel(string)` | `'Scheduled Jobs'` | Sidebar item label |
| `navigationIcon(string)` | `heroicon-o-clock` | Sidebar icon |
| `navigationSort(int)` | `99` | Sidebar sort order |
| `slug(string)` | `scheduled-jobs` | URL path for the resource |
| `heading(string)` | `'Scheduled Jobs'` | H1 on the list page |
| `subheading(string)` | `null` | Subtitle below H1 |
| `preventDuplicates(bool)` | `true` | Block duplicate job registrations |
| `groupActions(bool)` | `false` | Collapse row actions into a dropdown |
| `paginationPageOptions(array)` | `[25, 50, 100]` | Page size options |
| `defaultPaginationPageOption(int)` | `25` | Default page size |
| `withoutWidget()` | — | Do not register the stats widget |
| `withoutRunLog()` | — | Do not register the Run Logs page |
| `runLogNavigationLabel(string)` | `'Run Logs'` | Run Logs sidebar label |
| `runLogNavigationIcon(string)` | `heroicon-o-list-bullet` | Run Logs sidebar icon |
| `runLogSlug(string)` | `scheduled-job-runs` | Run Logs URL path |
| `runLogHeading(string)` | `'Run Logs'` | Run Logs H1 |
| `runLogPaginationPageOptions(array)` | `[25, 50, 100]` | Run Logs page size options |
| `runLogDefaultPaginationPageOption(int)` | `25` | Run Logs default page size |
| `policy(string)` | `null` | Custom Filament policy class |

## Authorisation

Pass a policy class to restrict access:

```php
TaskBridgePlugin::make()
    ->policy(App\Policies\ScheduledJobPolicy::class)
```

The policy is applied to `ScheduledJobResource`. Standard Filament policy methods apply: `viewAny`, `create`, `update`, `delete`, etc.

## Creating a job in the UI

When you select a job class in the Create form, TaskBridge automatically pre-fills:

- **Identifier** — derived from the class name + name prefix
- **Cron expression** — from `cronExpression()` if the method exists on the class
- **Group** — from `HasGroup::group()` if implemented, otherwise from the folder name

All of these can be edited before saving. The cron field is required only when the job class does not define a default.

## Labels and groups

Without any interface, TaskBridge derives readable values automatically:

- **Label**: `SendDailyReport` → `"Send daily report"`
- **Group**: `App\Jobs\Reporting\SendDailyReport` → `"Reporting"` (from folder)

Override either by implementing the corresponding interface:

```php
use CodeTechNL\TaskBridge\Contracts\HasCustomLabel;
use CodeTechNL\TaskBridge\Contracts\HasGroup;

class SendDailyReport implements HasCustomLabel, HasGroup, ShouldQueue
{
    public function taskLabel(): string
    {
        return 'Daily Report — Finance';
    }

    public function group(): string
    {
        return 'Finance'; // Overrides the folder-based detection.
    }
}
```

## Viewing structured job output

When a job implements `ReportsTaskOutput` and uses the `HasJobOutput` trait, execution metadata is stored on the run record. In the Run Logs table, the **Output** action opens a modal showing:

- Status badge (Success / Error / Warning / Info)
- Message text
- Key/value metadata table

Example:

```
Status:  Success
Message: Import complete

processed  | 1 420
skipped    | 38
duration   | 2.1s
```

## Run Logs in the job view

Opening a job's detail page (View) shows the run history inline via the **Run History** relation manager. Same columns and actions as the standalone Run Logs page.
