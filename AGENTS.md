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

## Further reading

- @README.md — plugin configuration and all available options
- @../laravel-taskbridge/docs/architecture.md — model schemas, enum values, contracts, execution paths
- @../laravel-taskbridge/AGENTS.md — core package rules that also apply here
