<?php

namespace CodeTechNL\TaskBridgeFilament\Resources\ScheduledJobResource\Pages;

use CodeTechNL\TaskBridge\Contracts\GroupedJob;
use CodeTechNL\TaskBridge\Contracts\ScheduledJob as ScheduledJobContract;
use CodeTechNL\TaskBridge\Facades\TaskBridge;
use CodeTechNL\TaskBridge\Models\ScheduledJob;
use CodeTechNL\TaskBridgeFilament\Resources\ScheduledJobResource;
use Filament\Resources\Pages\CreateRecord;

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

        if (class_exists($class) && is_a($class, ScheduledJobContract::class, true)) {
            $instance = new $class;
            $data['identifier'] = ScheduledJob::identifierFromClass($class);
            $data['cron_expression'] = $instance->cronExpression();
            $data['group'] = ($instance instanceof GroupedJob) ? $instance->group() : null;
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
