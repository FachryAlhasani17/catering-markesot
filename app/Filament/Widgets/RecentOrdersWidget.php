<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Pesanan Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('No. Pesanan')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Pelanggan'),

                Tables\Columns\TextColumn::make('event_date')
                    ->label('Tgl. Acara')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'gray',
                        'dp_paid'   => 'warning',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending'   => 'Menunggu DP',
                        'dp_paid'   => 'DP Dibayar',
                        'confirmed' => 'Dikonfirmasi',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default     => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ])
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Order $record) => route('filament.admin.resources.orders.view', $record)),
            ]);
    }
}
