<?php

namespace CodeTechNL\TaskBridgeFilament\Resources\ScheduledJobResource\Pages;

use CodeTechNL\TaskBridge\Enums\RunStatus;
use CodeTechNL\TaskBridge\Facades\TaskBridge;
use CodeTechNL\TaskBridgeFilament\Resources\ScheduledJobResource;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewScheduledJob extends ViewRecord
{
    protected static string $resource = ScheduledJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    try {
                        TaskBridge::getEventBridge()->remove($this->record->identifier);
                    } catch (\Throwable) {
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Job Details')
                    ->schema([
                        TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        Grid::make(3)->schema([
                            TextEntry::make('class')->label('Class')->columnSpan(2),
                            TextEntry::make('identifier')->label('Identifier'),
                            TextEntry::make('group')->label('Group')->placeholder('—'),
                            TextEntry::make('cron_expression')->label('Default Cron'),
                            TextEntry::make('cron_override')->label('Cron Override')->placeholder('—'),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('effective_cron')
                                ->label('Effective Cron')
                                ->badge()
                                ->color('gray'),
                            TextEntry::make('enabled')
                                ->label('Enabled')
                                ->badge()
                                ->formatStateUsing(fn (bool $state) => $state ? 'Enabled' : 'Disabled')
                                ->color(fn (bool $state) => $state ? 'success' : 'danger'),
                            TextEntry::make('last_status')
                                ->label('Last Status')
                                ->badge()
                                ->placeholder('—')
                                ->color(fn (?RunStatus $state) => $state?->color() ?? 'gray')
                                ->formatStateUsing(fn (?RunStatus $state) => $state?->label() ?? '—'),
                            TextEntry::make('last_run_at')->label('Last Run')->since()->placeholder('never'),
                        ]),
                    ]),
            ]);
    }
}
