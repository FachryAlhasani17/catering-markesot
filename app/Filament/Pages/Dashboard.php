<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\RecentOrdersWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dasbor';
    protected static ?string $title = 'Dasbor';

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            RecentOrdersWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 2;
    }
}
