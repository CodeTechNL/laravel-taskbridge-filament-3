<?php

namespace CodeTechNL\TaskBridgeFilament\Resources\ScheduledJobResource\RelationManagers;

use CodeTechNL\TaskBridge\Data\JobOutput;
use CodeTechNL\TaskBridge\Enums\RunStatus;
use CodeTechNL\TaskBridge\Enums\TriggeredBy;
use CodeTechNL\TaskBridge\Models\ScheduledJobRun;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RunsRelationManager extends RelationManager
{
    protected static string $relationship = 'runs';

    protected static ?string $title = 'Run History';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('started_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (RunStatus $state) => $state->color())
                    ->formatStateUsing(fn (RunStatus $state) => $state->label()),

                Tables\Columns\TextColumn::make('triggered_by')
                    ->label('Trigger')
                    ->badge()
                    ->color(fn (TriggeredBy $state) => $state->color())
                    ->formatStateUsing(fn (TriggeredBy $state) => $state->label()),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(function (?int $state) {
                        if ($state === null) {
                            return '—';
                        }
                        if ($state < 1000) {
                            return "{$state}ms";
                        }

                        return number_format($state / 1000, 2).'s';
                    }),

                Tables\Columns\TextColumn::make('jobs_dispatched')
                    ->label('Jobs Dispatched'),

                Tables\Columns\TextColumn::make('output')
                    ->label('Output')
                    ->badge()
                    ->formatStateUsing(fn (?array $state) => $state ? JobOutput::fromArray($state)->label() : null)
                    ->color(fn (?array $state) => $state ? JobOutput::fromArray($state)->color() : 'gray')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(
                        collect(RunStatus::cases())
                            ->mapWithKeys(fn (RunStatus $case) => [$case->value => $case->label()])
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('triggered_by')
                    ->label('Trigger')
                    ->options(
                        collect(TriggeredBy::cases())
                            ->mapWithKeys(fn (TriggeredBy $case) => [$case->value => $case->label()])
                            ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('view_output')
                    ->label('Output')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn (ScheduledJobRun $record) => ! empty($record->output))
                    ->modalHeading('Job Output')
                    ->modalContent(fn (ScheduledJobRun $record) => view(
                        'taskbridge-filament::modals.output-detail',
                        ['output' => $record->output]
                    )),

            ]);
    }
}
