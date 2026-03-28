<?php

namespace CodeTechNL\TaskBridgeFilament\Resources\ScheduledJobResource\Pages;

use CodeTechNL\TaskBridge\Facades\TaskBridge;
use CodeTechNL\TaskBridgeFilament\Resources\ScheduledJobResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScheduledJob extends EditRecord
{
    protected static string $resource = ScheduledJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    try {
                        TaskBridge::getEventBridge()->remove($this->record->identifier);
                    } catch (\Throwable) {
                    }
                }),
            Actions\ViewAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record->fresh();

        try {
            if ($record->enabled) {
                TaskBridge::enable($record->class);
            } else {
                TaskBridge::disable($record->class);
            }
        } catch (\Throwable) {
            // Non-fatal: record is saved, sync can be re-run manually
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
