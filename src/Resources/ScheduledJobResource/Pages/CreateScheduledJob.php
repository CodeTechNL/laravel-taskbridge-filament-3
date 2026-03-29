<?php

namespace CodeTechNL\TaskBridgeFilament\Resources\ScheduledJobResource\Pages;

use CodeTechNL\TaskBridge\Facades\TaskBridge;
use CodeTechNL\TaskBridge\Models\ScheduledJob;
use CodeTechNL\TaskBridge\Support\JobInspector;
use CodeTechNL\TaskBridgeFilament\Resources\ScheduledJobResource;
use CodeTechNL\TaskBridgeFilament\Support\JobFormBuilder;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateScheduledJob extends CreateRecord
{
    protected static string $resource = ScheduledJobResource::class;

    public function getTitle(): string
    {
        return 'Add scheduled job';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $class = $data['class'];

        if (class_exists($class) && is_a($class, ShouldQueue::class, true)) {
            $instance = JobInspector::make($class);
            $data['identifier'] = ScheduledJob::identifierFromClass($class);

            // Store the class default separately from the user-provided override.
            // cronExpression() is optional — DB column is nullable.
            $data['cron_expression'] = method_exists($instance, 'cronExpression')
                ? $instance->cronExpression()
                : null;

            // Prefer the group already set via the form (auto-detected or user-typed).
            // Fall back to resolveGroup() so the DB is always populated correctly.
            $data['group'] = $data['group'] ?? ScheduledJobResource::resolveGroup($class);
        }

        // Collect arg_* fields into a positional constructor_arguments array,
        // then remove them so they don't land in unknown DB columns.
        $data['constructor_arguments'] = JobFormBuilder::resolveArguments($class, $data);
        foreach (array_keys($data) as $key) {
            if (str_starts_with($key, 'arg_')) {
                unset($data[$key]);
            }
        }

        // Strip internal hint fields — they are dehydrated(false) but guard anyway
        unset($data['_identifier_hint'], $data['_default_cron_hint']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->enabled) {
            try {
                TaskBridge::enable($this->record->class);
            } catch (\Throwable) {
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
