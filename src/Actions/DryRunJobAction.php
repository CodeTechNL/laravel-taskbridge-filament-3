<?php

namespace CodeTechNL\TaskBridgeFilament\Actions;

use CodeTechNL\TaskBridge\Facades\TaskBridge;
use CodeTechNL\TaskBridge\Models\ScheduledJob;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class DryRunJobAction extends Action
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'dry-run')
            ->label('Dry run')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Dry run job')
            ->modalDescription('This simulates the job without actually dispatching anything. Continue?')
            ->action(function (ScheduledJob $record) {
                try {
                    $run = TaskBridge::run($record->class, dryRun: true, force: true);

                    Notification::make()
                        ->title('Dry run complete')
                        ->body('Status: '.$run->status->label().' | Would dispatch: '.$run->jobs_dispatched)
                        ->info()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Dry run failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
