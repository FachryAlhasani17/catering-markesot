<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PendingPaymentsWidget;
use App\Filament\Widgets\RecentOrdersWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Dashboard Catering Markesot';

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            RecentOrdersWidget::class,
            PendingPaymentsWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 2;
    }
}
