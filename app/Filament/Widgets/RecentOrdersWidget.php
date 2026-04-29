<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class RecentOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Riwayat Pesanan';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::latest())
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('No. Pesanan')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable(),

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
                        'pending'   => 'Menunggu Verifikasi',
                        'dp_paid'   => 'DP Dibayar',
                        'confirmed' => 'Sedang Dimasak',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default     => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->label('Tanggal Transaksi')
                    ->form([
                        DatePicker::make('date')->label('Pilih Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['date'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', $date),
                        );
                    }),
            ])
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Order $record) => route('filament.admin.resources.orders.view', $record)),
            ]);
    }
}
