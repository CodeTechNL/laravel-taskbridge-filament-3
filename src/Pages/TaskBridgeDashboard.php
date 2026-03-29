<?php

namespace CodeTechNL\TaskBridgeFilament\Pages;

use CodeTechNL\TaskBridgeFilament\TaskBridgePlugin;
use CodeTechNL\TaskBridgeFilament\Widgets\AverageDurationChart;
use CodeTechNL\TaskBridgeFilament\Widgets\JobStatsOverview;
use CodeTechNL\TaskBridgeFilament\Widgets\MissedJobsAlert;
use CodeTechNL\TaskBridgeFilament\Widgets\RecentFailuresWidget;
use CodeTechNL\TaskBridgeFilament\Widgets\RunHistoryChart;
use CodeTechNL\TaskBridgeFilament\Widgets\UpcomingJobsWidget;
use Filament\Pages\Dashboard;

class TaskBridgeDashboard extends Dashboard
{
    protected static string $routePath = 'taskbridge';

    public static function getNavigationGroup(): ?string
    {
        return TaskBridgePlugin::get()->getDashboardNavigationGroup();
    }

    public static function getNavigationLabel(): string
    {
        return TaskBridgePlugin::get()->getDashboardNavigationLabel();
    }

    public static function getNavigationIcon(): string
    {
        return TaskBridgePlugin::get()->getDashboardNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return TaskBridgePlugin::get()->getDashboardNavigationSort();
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return TaskBridgePlugin::get()->getDashboardTitle();
    }

    public function getWidgets(): array
    {
        return [
            MissedJobsAlert::class,
            JobStatsOverview::class,
            RunHistoryChart::class,
            AverageDurationChart::class,
            RecentFailuresWidget::class,
            UpcomingJobsWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
