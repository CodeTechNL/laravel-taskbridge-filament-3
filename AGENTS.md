# AGENTS.md — taskbridge-filament-3

This file guides AI agents working in this package.

## Package identity

- **Composer name:** `codetechnl/laravel-taskbridge-filament-3`
- **Root namespace:** `CodeTechNL\TaskBridgeFilament\`
- **PHP:** ^8.3 | **Filament:** ^3.2
- **Depends on:** `codetechnl/laravel-taskbridge`

## Code style — mandatory rule

**After every code change, run Laravel Pint before finishing:**

```bash
./vendor/bin/pint
```

Never skip this step. The project uses the default Pint ruleset. If Pint is not installed in this package's vendor, run `composer install` first.

## Source layout

```
src/
  Actions/
    DryRunJobAction.php       — table row action: dry-run via TaskBridge::run(dryRun: true)
    RunJobAction.php          — table row action: force-run via TaskBridge::run(force: true)
    SyncAction.php            — header action: sync all enabled jobs to EventBridge
    ValidateJobsAction.php    — header action: check class existence + contract compliance
  Resources/
    ScheduledJobResource.php                   — main CRUD resource
    ScheduledJobRunResource.php                — read-only run log resource
    ScheduledJobResource/
      Pages/
        CreateScheduledJob.php
        EditScheduledJob.php
        ListScheduledJobs.php
        ViewScheduledJob.php
      RelationManagers/
        RunsRelationManager.php               — inline run history on view page
  Widgets/
    TaskBridgeWidget.php                      — stats overview (total/active/disabled/failed 24h)
  TaskBridgeFilamentServiceProvider.php
  TaskBridgePlugin.php                        — fluent plugin configuration
resources/
  views/
    modals/
      output-detail.blade.php               — status badge + message + metadata table
```

## Critical architectural rules

### 1. Plugin singleton pattern

`TaskBridgePlugin::get()` is the canonical way to read plugin configuration from resource static methods. During a real Filament request, `filament()->getPlugin('taskbridge')` returns the booted instance. During tests or artisan commands, it falls back to `new static` (default values). Never inject the plugin via constructor — always call `TaskBridgePlugin::get()`.

### 2. Enum-typed closures in columns

`RunStatus` and `TriggeredBy` are backed PHP enums cast by Eloquent. Filament column closures receive the cast value — always type-hint with the enum, never `string`:

```php
// Correct
->color(fn (RunStatus $state) => $state->color())
->formatStateUsing(fn (RunStatus $state) => $state->label())

// Wrong — will throw TypeError at runtime
->color(fn (string $state) => ...)
```

### 3. Output column uses JobOutput::fromArray

The `output` column on `ScheduledJobRun` stores a JSON array. Filament reads it as a PHP array. Always reconstruct via `JobOutput::fromArray($state)`:

```php
->formatStateUsing(fn (?array $state) => $state ? JobOutput::fromArray($state)->label() : null)
->color(fn (?array $state) => $state ? JobOutput::fromArray($state)->color() : 'gray')
```

### 4. getModel() override on resources

Both resources override `getModel()` to respect the config override:

```php
public static function getModel(): string
{
    return config('taskbridge.models.scheduled_job', ScheduledJob::class);
}
```

This means custom model classes defined in `taskbridge.models` are automatically used. Do not hardcode model class names inside resource methods.

### 5. Actions call TaskBridge facade

Row actions (`RunJobAction`, `DryRunJobAction`) call `TaskBridge::run()` via the facade. The `$record` passed to the action closure is always a `ScheduledJob` model instance. Never dispatch the job class directly.

```php
// Correct
$run = TaskBridge::run($record->class, force: true);

// Wrong
dispatch(new ($record->class));
```

### 6. Status interpolation is forbidden

`RunStatus` enum instances cannot be used in PHP string interpolation. Always call `->label()`:

```php
// Correct
'Job dispatched: ' . $run->status->label()

// Wrong — throws: Object of class RunStatus could not be converted to string
"Job dispatched: {$run->status}"
```

### 7. Page actions call getEventBridge(), not getDriver()

The stale `getDriver()` method was removed. All pages that reference the EventBridge driver must call:

```php
\CodeTechNL\TaskBridge\Facades\TaskBridge::getEventBridge()
```

### 8. No BadgeColumn

Filament 3 does not have `BadgeColumn`. Use `TextColumn->badge()` instead. This was a migration from Filament 2 — do not reintroduce `BadgeColumn`.

### 9. output-detail modal receives the raw array

The modal view `taskbridge-filament::modals.output-detail` receives `['output' => $record->output]` where `$record->output` is a PHP array (Eloquent casts the JSON column). The view reads `$output['status']`, `$output['message']`, `$output['metadata']` directly.

## Key configuration options

All read via `TaskBridgePlugin::get()`:

| Method | Default | Description |
|--------|---------|-------------|
| `getNavigationGroup()` | `'System'` | Sidebar group |
| `getSlug()` | `'scheduled-jobs'` | URL prefix |
| `getNavigationSort()` | `99` | Sidebar order |
| `shouldPreventDuplicates()` | `true` | Block same class being added twice |
| `shouldGroupActions()` | `false` | Collapse row actions into dropdown |
| `getPaginationPageOptions()` | `[25, 50, 100]` | Page size options |
| `shouldRegisterRunLog()` | `true` | Register run log resource |
| `getRunLogSlug()` | `'scheduled-job-runs'` | Run log URL prefix |

## Common mistakes to avoid

- Using `BadgeColumn` — use `TextColumn->badge()`
- String interpolation of `RunStatus`/`TriggeredBy` enum instances — call `->label()`
- Type-hinting `?string` in column closures for enum-cast fields — use the enum type
- Calling `TaskBridge::getDriver()` — the method is `getEventBridge()`
- Hardcoding model classes instead of `config('taskbridge.models.*')`
- Accessing `$record->error` — that column was removed; errors are in `$record->output['message']`
- Building the plugin with `new TaskBridgePlugin` directly — always use `TaskBridgePlugin::make()`
