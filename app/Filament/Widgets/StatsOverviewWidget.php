<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalOrders    = Order::count();
        $pendingOrders  = Order::where('status', 'pending')->count();
        $confirmedOrders = Order::where('status', 'confirmed')->count();
        $completedOrders = Order::where('status', 'completed')->count();

        $pendingPayments = Payment::where('status', 'pending')->count();

        $totalRevenue = Payment::where('status', 'verified')->sum('amount');

        return [
            Stat::make('Total Pesanan', $totalOrders)
                ->description('Semua pesanan masuk')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('primary')
                ->chart([
                    Order::whereDate('created_at', today()->subDays(6))->count(),
                    Order::whereDate('created_at', today()->subDays(5))->count(),
                    Order::whereDate('created_at', today()->subDays(4))->count(),
                    Order::whereDate('created_at', today()->subDays(3))->count(),
                    Order::whereDate('created_at', today()->subDays(2))->count(),
                    Order::whereDate('created_at', today()->subDays(1))->count(),
                    Order::whereDate('created_at', today())->count(),
                ]),

            Stat::make('Menunggu DP', $pendingOrders)
                ->description('Pesanan belum bayar DP')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Dikonfirmasi', $confirmedOrders)
                ->description('Pesanan sedang diproses')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('info'),

            Stat::make('Selesai', $completedOrders)
                ->description('Pesanan berhasil selesai')
                ->descriptionIcon('heroicon-o-flag')
                ->color('success'),

            Stat::make('Pembayaran Menunggu', $pendingPayments)
                ->description('Perlu diverifikasi')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($pendingPayments > 0 ? 'danger' : 'gray'),

            Stat::make('Total Pendapatan', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('Dari pembayaran terverifikasi')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
        ];
    }
}
