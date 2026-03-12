<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingPaymentsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Pembayaran Menunggu Verifikasi';

    public function table(Table $table): Table
    {
        return $table
            ->query(Payment::where('status', 'pending')->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('No. Pembayaran')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('No. Pesanan'),

                Tables\Columns\TextColumn::make('order.customer_name')
                    ->label('Pelanggan'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'dp'         => 'warning',
                        'settlement' => 'info',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state === 'dp' ? 'Down Payment' : 'Pelunasan'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR'),

                Tables\Columns\ImageColumn::make('proof_image')
                    ->label('Bukti')
                    ->circular(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diterima')
                    ->dateTime('d M Y H:i'),
            ])
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->url(fn (Payment $record) => route('filament.admin.resources.payments.view', $record)),
            ]);
    }
}
