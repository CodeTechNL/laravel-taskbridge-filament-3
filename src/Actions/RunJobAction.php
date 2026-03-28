<?php

namespace CodeTechNL\TaskBridgeFilament\Actions;

use CodeTechNL\TaskBridge\Facades\TaskBridge;
use CodeTechNL\TaskBridge\Models\ScheduledJob;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class RunJobAction extends Action
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'run')
            ->label('Run now')
            ->icon('heroicon-o-play')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Run job now')
            ->modalDescription('This will immediately dispatch the job. Continue?')
            ->action(function (ScheduledJob $record) {
                try {
                    $run = TaskBridge::run($record->class, force: true);

                    Notification::make()
                        ->title('Job dispatched: '.$run->status->label())
                        ->body("Duration: {$run->duration_ms}ms | Dispatched: {$run->jobs_dispatched}")
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Job failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
