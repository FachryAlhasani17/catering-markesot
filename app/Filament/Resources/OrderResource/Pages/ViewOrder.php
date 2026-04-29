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
        return [];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Grid::make(['default' => 1, 'md' => 3])->schema([
                \Filament\Schemas\Components\Group::make([
                    Section::make('Informasi Pesanan')->schema([
                        Infolists\Components\TextEntry::make('order_number')
                            ->label('No. Pesanan')
                            ->weight('bold')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Tgl. Pesanan')
                            ->dateTime('d M Y H:i'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Status Pesanan')
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
                    ])->columns(2),

                    Section::make('Daftar Menu')->schema([
                        Infolists\Components\RepeatableEntry::make('orderItems')
                            ->label('')
                            ->contained(false)
                            ->schema([
                                Infolists\Components\TextEntry::make('menu_name')
                                    ->label('Menu')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->columnSpanFull(),
                                
                                \Filament\Schemas\Components\Grid::make(3)->schema([
                                    Infolists\Components\TextEntry::make('quantity')
                                        ->label('Jumlah')
                                        ->formatStateUsing(fn ($state, $record) => $state . ' ' . $record->unit),
                                    Infolists\Components\TextEntry::make('menu_price')
                                        ->label('Harga Satuan')
                                        ->money('IDR'),
                                    Infolists\Components\TextEntry::make('subtotal')
                                        ->label('Subtotal')
                                        ->money('IDR')
                                        ->weight('bold')
                                        ->color('primary'),
                                ]),

                                Infolists\Components\TextEntry::make('notes')
                                    ->label('Catatan Tambahan')
                                    ->placeholder('Tidak ada catatan')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                    Section::make('Rincian Pembayaran')->schema([
                        Infolists\Components\TextEntry::make('payments.payment_method')
                            ->label('Tipe Pembayaran')
                            ->badge()
                            ->formatStateUsing(function ($state, $record) {
                                $method = $record->payments()->latest()->first()?->payment_method ?? $state;
                                if ($method === 'cash') return 'Tunai';
                                if ($method === 'bank' && $record->dp_percentage >= 100) return 'Transfer Lunas';
                                return 'DP Transfer';
                            })
                            ->color(function ($state, $record) {
                                $method = $record->payments()->latest()->first()?->payment_method ?? $state;
                                if ($method === 'cash') return 'success';
                                if ($method === 'bank' && $record->dp_percentage >= 100) return 'info';
                                return 'warning';
                            }),
                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Total Pesanan')
                            ->money('IDR')
                            ->weight('bold')
                            ->size('lg'),
                        Infolists\Components\TextEntry::make('dp_percentage')
                            ->label('DP')
                            ->formatStateUsing(fn ($state) => $state . '%'),
                        Infolists\Components\TextEntry::make('dp_amount')
                            ->label('Nominal DP / Dibayar')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('remaining_amount')
                            ->label('Sisa Kekurangan')
                            ->money('IDR')
                            ->color('danger')
                            ->weight('bold')
                            ->hidden(fn ($record) => ($record->remaining_amount ?? 0) <= 0),
                        Infolists\Components\ImageEntry::make('payments.proof_image')
                            ->label('Bukti Transfer')
                            ->columnSpanFull()
                            ->disk('public')
                            ->hidden(fn ($record) => $record->payments()->whereNotNull('proof_image')->count() === 0)
                            ->extraImgAttributes(['style' => 'max-height: 400px; object-fit: contain; border-radius: 8px; border: 1px solid #ddd;']),
                    ])->columns(2),
                ])->columnSpan(['default' => 1, 'md' => 2]),

                \Filament\Schemas\Components\Group::make([
                    Section::make('Data Pelanggan')->schema([
                        Infolists\Components\TextEntry::make('customer_name')
                            ->label('Nama')
                            ->icon('heroicon-o-user'),
                        Infolists\Components\TextEntry::make('customer_phone')
                            ->label('Telepon')
                            ->icon('heroicon-o-phone')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('customer_email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('customer_address')
                            ->label('Alamat')
                            ->icon('heroicon-o-map-pin'),
                    ])->columns(1),

                    Section::make('Detail Acara')->schema([
                        Infolists\Components\TextEntry::make('event_date')
                            ->label('Waktu Acara (Tanggal & Jam)')
                            ->dateTime('d M Y, H:i')
                            ->icon('heroicon-o-calendar'),
                        Infolists\Components\TextEntry::make('total_pax')
                            ->label('Jumlah Tamu')
                            ->icon('heroicon-o-users')
                            ->placeholder('—'),
                    ])->columns(1),

                    Section::make('Catatan Khusus')->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Catatan Pelanggan')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label('Catatan Penolakan')
                            ->placeholder('—')
                            ->color('danger')
                            ->hidden(fn ($record) => empty($record->payments()->latest()->first()?->rejection_reason))
                            ->getStateUsing(fn ($record) => $record->payments()->latest()->first()?->rejection_reason),
                    ])->columns(1),
                ])->columnSpan(['default' => 1, 'md' => 1]),
            ]),
        ]);
    }
}
