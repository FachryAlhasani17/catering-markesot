<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('confirm')
                ->label('Konfirmasi Pesanan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === Order::STATUS_DP_PAID)
                ->action(function () {
                    app(OrderService::class)->confirm($this->record);
                    $this->refreshFormData(['status', 'confirmed_at']);
                    Notification::make()->title('Pesanan dikonfirmasi!')->success()->send();
                }),

            Actions\Action::make('complete')
                ->label('Tandai Selesai')
                ->icon('heroicon-o-flag')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === Order::STATUS_CONFIRMED)
                ->action(function () {
                    app(OrderService::class)->complete($this->record);
                    $this->refreshFormData(['status', 'completed_at']);
                    Notification::make()->title('Pesanan selesai!')->success()->send();
                }),

            Actions\Action::make('cancel')
                ->label('Batalkan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => !in_array($this->record->status, [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED]))
                ->form([
                    \Filament\Forms\Components\Textarea::make('cancellation_reason')
                        ->label('Alasan Pembatalan')
                        ->required(),
                ])
                ->action(function (array $data) {
                    app(OrderService::class)->cancel($this->record, $data['cancellation_reason']);
                    $this->refreshFormData(['status', 'cancelled_at', 'cancellation_reason']);
                    Notification::make()->title('Pesanan dibatalkan.')->warning()->send();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pesanan')->schema([
                Infolists\Components\TextEntry::make('order_number')
                    ->label('No. Pesanan')
                    ->weight('bold')
                    ->copyable(),

                Infolists\Components\TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
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

                Infolists\Components\TextEntry::make('payment_status')
                    ->label('Status Pembayaran')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'unpaid'     => 'danger',
                        'dp_paid'    => 'warning',
                        'fully_paid' => 'success',
                        'refunded'   => 'gray',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'unpaid'     => 'Belum Bayar',
                        'dp_paid'    => 'DP Lunas',
                        'fully_paid' => 'Lunas',
                        'refunded'   => 'Direfund',
                        default      => $state,
                    }),

                Infolists\Components\TextEntry::make('created_at')
                    ->label('Tgl. Pesanan')
                    ->dateTime('d M Y H:i'),
            ])->columns(4),

            Section::make('Data Pelanggan')->schema([
                Infolists\Components\TextEntry::make('customer_name')
                    ->label('Nama'),
                Infolists\Components\TextEntry::make('customer_phone')
                    ->label('Telepon'),
                Infolists\Components\TextEntry::make('customer_email')
                    ->label('Email')
                    ->placeholder('—'),
                Infolists\Components\TextEntry::make('customer_address')
                    ->label('Alamat')
                    ->columnSpanFull(),
            ])->columns(3),

            Section::make('Detail Acara')->schema([
                Infolists\Components\TextEntry::make('event_date')
                    ->label('Tanggal Acara')
                    ->date('d M Y'),
                Infolists\Components\TextEntry::make('event_time')
                    ->label('Waktu')
                    ->placeholder('—'),
                Infolists\Components\TextEntry::make('total_pax')
                    ->label('Jumlah Tamu')
                    ->placeholder('—'),
            ])->columns(3),

            Section::make('Item Pesanan')->schema([
                Infolists\Components\RepeatableEntry::make('orderItems')
                    ->label('')
                    ->schema([
                        Infolists\Components\TextEntry::make('menu_name')
                            ->label('Menu'),
                        Infolists\Components\TextEntry::make('menu_price')
                            ->label('Harga Satuan')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('quantity')
                            ->label('Qty'),
                        Infolists\Components\TextEntry::make('unit')
                            ->label('Satuan'),
                        Infolists\Components\TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->money('IDR')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('—'),
                    ])
                    ->columns(6)
                    ->columnSpanFull(),
            ]),

            Section::make('Rincian Pembayaran')->schema([
                Infolists\Components\TextEntry::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR'),
                Infolists\Components\TextEntry::make('discount_amount')
                    ->label('Diskon')
                    ->money('IDR'),
                Infolists\Components\TextEntry::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->weight('bold'),
                Infolists\Components\TextEntry::make('dp_percentage')
                    ->label('DP')
                    ->formatStateUsing(fn ($state) => $state . '%'),
                Infolists\Components\TextEntry::make('dp_amount')
                    ->label('Nominal DP')
                    ->money('IDR'),
                Infolists\Components\TextEntry::make('remaining_amount')
                    ->label('Sisa Bayar')
                    ->money('IDR')
                    ->color('danger'),
            ])->columns(3),

            Section::make('Catatan')->schema([
                Infolists\Components\TextEntry::make('notes')->label('Catatan Pelanggan')->placeholder('—'),
                Infolists\Components\TextEntry::make('admin_notes')->label('Catatan Admin')->placeholder('—'),
                Infolists\Components\TextEntry::make('cancellation_reason')->label('Alasan Batal')->placeholder('—'),
            ])->columns(3),
        ]);
    }
}
