<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Services\SettingService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'Menunggu Verifikasi' => \Filament\Schemas\Components\Tabs\Tab::make('Menunggu Verifikasi')
                ->badge(\App\Models\Order::where('status', 'pending')->count())
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'pending')),
            'Sedang Dimasak' => \Filament\Schemas\Components\Tabs\Tab::make('Sedang Dimasak (Tunggu Selesai)')
                ->badge(\App\Models\Order::whereIn('status', ['confirmed', 'dp_paid'])->count())
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereIn('status', ['confirmed', 'dp_paid'])),
        ];
    }
}
