<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use Filament\Widgets\ChartWidget;

class TopMenuChart extends ChartWidget
{
    protected ?string $heading = 'Menu Paling Laris (Per Bulan)';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected ?string $maxHeight = '300px';

    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $date = now()->subMonths($i);
            $months[$date->format('Y-m')] = $date->translatedFormat('F Y');
        }
        return $months;
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter ?? now()->format('Y-m');
        $year = substr($activeFilter, 0, 4);
        $month = substr($activeFilter, 5, 2);

        $topMenus = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereYear('orders.created_at', $year)
            ->whereMonth('orders.created_at', $month)
            ->whereIn('orders.status', ['completed', 'confirmed', 'dp_paid', 'pending'])
            ->select('order_items.menu_name', DB::raw('SUM(order_items.quantity) as total_quantity'))
            ->groupBy('order_items.menu_name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Porsi Terjual',
                    'data' => $topMenus->pluck('total_quantity')->toArray(),
                    'backgroundColor' => '#8B2535', // maroon light
                    'borderColor' => '#6B1C2A', // maroon
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $topMenus->pluck('menu_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
