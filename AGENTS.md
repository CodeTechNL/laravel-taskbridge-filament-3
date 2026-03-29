# AGENTS.md — taskbridge-filament-3

For full context on this package, read @README.md. For the core package rules, read @../laravel-taskbridge/AGENTS.md.

## Commands

Run after every code change — must pass before finishing:

```bash
./vendor/bin/pint   # code style (default ruleset)
```

If `vendor/` is missing, run `composer install` first.

## Git

**Never create commits unless explicitly requested by the user.**

## Rules

**Interface names are final.** The four optional interfaces from the core package are: `RunsConditionally`, `HasGroup`, `HasCustomLabel`, `ReportsTaskOutput`. Do not use or reference the old names (`ConditionalJob`, `GroupedJob`, `LabeledJob`, `ReportsOutput`). Do not reference `ScheduledJob` — it no longer exists.

**`ReportsTaskOutput` requires `reportOutput()`.** The interface now declares `reportOutput(array $metadata): void` — it is no longer a marker. Add the `HasJobOutput` trait to satisfy it:
```php
class ImportProducts implements ReportsTaskOutput, ShouldQueue
{
    use HasJobOutput; // satisfies reportOutput()
}
```

**Always use enum cases in column closures.** `RunStatus` and `TriggeredBy` are Eloquent-cast enums. Filament passes the cast value, not a string:
```php
// correct
->color(fn (RunStatus $state) => $state->color())
// wrong — throws TypeError at runtime
->color(fn (string $state) => ...)
```

**Never interpolate enum instances as strings.** Always call `->label()`:
```php
// correct
'Status: ' . $run->status->label()
// wrong — throws: Object of class RunStatus could not be converted to string
"Status: {$run->status}"
```

**Output column always uses `JobOutput::fromArray()`.** The `output` column is a PHP array (Eloquent JSON cast). Never access keys directly in column definitions:
```php
->formatStateUsing(fn (?array $state) => $state ? JobOutput::fromArray($state)->label() : null)
```

**`resolveLabel()` and `resolveGroup()` are the single source of truth.** Both label and group fallback logic lives in `ScheduledJobResource::resolveLabel()` and `resolveGroup()`. Use these helpers everywhere — do not inline the detection logic.

**`TaskBridgePlugin::get()` is the only way to read plugin config.** Never inject the plugin via constructor or instantiate it with `new`. During tests it falls back to defaults automatically.

**Always resolve model classes via config:**
```php
config('taskbridge.models.scheduled_job', ScheduledJob::class)
```

**Row actions call `TaskBridge::run()`, never `dispatch()` directly:**
```php
// correct
$run = TaskBridge::run($record->class, force: true);
// wrong
dispatch(new ($record->class));
```

**The driver method is `getEventBridge()`, not `getDriver()`.**

**No `BadgeColumn`.** Filament 3 uses `TextColumn->badge()` only.

**One-time jobs are read-only in the table.** Run now, Dry run, and Edit row actions are hidden for one-time jobs (`$record->isOnce()`). The enabled toggle is also disabled for them. Never show `ScheduleOnceAction` as a row action — it was removed from the table entirely.

**`ScheduleOnceAction` is not a table row action.** One-time scheduling is done from outside the table (e.g. a header action or a separate flow). Do not re-add it to `buildRowActions()`.

**Bool constructor parameters render as a Select, not a Toggle.** The options are `['1' => 'True', '0' => 'False']`. Never use `Toggle` for constructor argument fields:
```php
// correct
Select::make($fieldName)->options(['1' => 'True', '0' => 'False'])
// wrong
Toggle::make($fieldName)
```

**The Constructor Arguments section is always visible.** It never conditionally hides itself. Instead it shows one of three states based on `$get('class')`:
1. No class selected → `Placeholder` with "No job has been selected."
2. Class has no scalar params → `Placeholder` with "This job doesn't have any arguments."
3. Class has scalar params → the fields from `JobFormBuilder::buildFields()`

**Form layout: Constructor Arguments (span 2) + Retry Policy (span 1) in a Grid::make(3).** Always place these two sections together in this grid. Constructor Arguments comes first (left, 66%), Retry Policy comes second (right, 33%).

**`buildClassOptions()` uses `ScheduledJob::recurring()` scope.** When checking for already-registered ("taken") classes, scope the query to recurring jobs only — one-time job rows must not mark a class as taken.

**The `_status_dot` column is a `ColorColumn`.** It derives its color from `$record->last_status?->color() ?? 'gray'`. It has no label and a fixed width of `4px`. It is the leftmost column in the table.

**Activate/Deactivate on the view page use `$this->record = $this->record->fresh()` to refresh.** `refreshFormData()` does not exist on `ViewRecord`. After the action runs, reload the record with `->fresh()` to reflect the new enabled state in the infolist.

## Further reading

- @README.md — plugin configuration and all available options
- @../laravel-taskbridge/docs/architecture.md — model schemas, enum values, contracts, execution paths
- @../laravel-taskbridge/AGENTS.md — core package rules that also apply here
