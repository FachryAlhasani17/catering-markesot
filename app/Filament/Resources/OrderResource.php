<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\MenuItem;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Pesanan';
    protected static ?string $modelLabel = 'Pesanan';
    protected static ?string $pluralModelLabel = 'Pesanan';
    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereIn('status', ['pending', 'dp_paid', 'confirmed'])->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // --- Data Pelanggan ---
            Section::make('Data Pelanggan')->schema([
                TextInput::make('customer_name')
                    ->label('Nama Pelanggan')
                    ->required()
                    ->maxLength(255),

                TextInput::make('customer_phone')
                    ->label('No. Telepon')
                    ->required()
                    ->tel()
                    ->maxLength(20),

                TextInput::make('customer_email')
                    ->label('Email')
                    ->email()
                    ->nullable()
                    ->maxLength(255),

                Textarea::make('customer_address')
                    ->label('Alamat Pengiriman')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),

            // --- Detail Acara ---
            Section::make('Detail Acara')->schema([
                DatePicker::make('event_date')
                    ->label('Tanggal Acara')
                    ->required()
                    ->native(false)
                    ->minDate(now()),

                TimePicker::make('event_time')
                    ->label('Waktu Acara')
                    ->nullable(),

                TextInput::make('total_pax')
                    ->label('Jumlah Tamu (Pax)')
                    ->numeric()
                    ->nullable()
                    ->minValue(1),
            ])->columns(3),

            // --- Item Menu (Repeater) ---
            Section::make('Item Pesanan')->schema([
                Repeater::make('orderItems')
                    ->label('Daftar Menu')
                    ->relationship()
                    ->schema([
                        Select::make('menu_item_id')
                            ->label('Menu')
                            ->options(
                                MenuItem::available()
                                    ->with('category')
                                    ->get()
                                    ->groupBy(fn ($item) => $item->category?->name ?? 'Tanpa Kategori')
                                    ->map(fn ($items) => $items->pluck('name', 'id'))
                                    ->toArray()
                            )
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $menu = MenuItem::find($state);
                                if ($menu) {
                                    $set('menu_name',  $menu->name);
                                    $set('menu_price', $menu->price);
                                    $set('unit',       $menu->unit);
                                    $set('subtotal',   $menu->price * 1);
                                }
                            })
                            ->columnSpan(2),

                        TextInput::make('menu_name')
                            ->label('Nama Menu (Snapshot)')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('menu_price')
                            ->label('Harga Satuan')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                $qty = (float) ($get('quantity') ?? 1);
                                $set('subtotal', (float) $state * $qty);
                            }),

                        TextInput::make('quantity')
                            ->label('Jumlah')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                $price = (float) ($get('menu_price') ?? 0);
                                $set('subtotal', $price * (float) $state);
                            }),

                        TextInput::make('unit')
                            ->label('Satuan')
                            ->default('porsi')
                            ->required(),

                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly(),

                        TextInput::make('notes')
                            ->label('Catatan')
                            ->nullable()
                            ->columnSpan(2),
                    ])
                    ->columns(4)
                    ->addActionLabel('+ Tambah Menu')
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::recalcTotals($get, $set);
                    })
                    ->deleteAction(fn ($action) => $action->after(
                        function (Get $get, Set $set) {
                            self::recalcTotals($get, $set);
                        }
                    ))
                    ->columnSpanFull(),
            ]),

            // --- Kalkulasi Total ---
            Section::make('Total Pembayaran')->schema([
                TextInput::make('subtotal')
                    ->label('Subtotal')
                    ->prefix('Rp')
                    ->readOnly()
                    ->numeric(),

                TextInput::make('discount_amount')
                    ->label('Diskon')
                    ->prefix('Rp')
                    ->numeric()
                    ->default(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcTotals($get, $set)),

                TextInput::make('total_amount')
                    ->label('Total')
                    ->prefix('Rp')
                    ->readOnly()
                    ->numeric(),

                TextInput::make('dp_percentage')
                    ->label('DP (%)')
                    ->suffix('%')
                    ->numeric()
                    ->readOnly()
                    ->dehydrated(true),

                TextInput::make('dp_amount')
                    ->label('Nominal DP')
                    ->prefix('Rp')
                    ->readOnly()
                    ->numeric(),

                TextInput::make('remaining_amount')
                    ->label('Sisa Pembayaran')
                    ->prefix('Rp')
                    ->readOnly()
                    ->numeric(),
            ])->columns(3),

            // --- Catatan ---
            Section::make('Catatan')->schema([
                Textarea::make('notes')
                    ->label('Catatan Pelanggan')
                    ->rows(2)
                    ->nullable(),

                Textarea::make('admin_notes')
                    ->label('Catatan Admin')
                    ->rows(2)
                    ->nullable(),
            ])->columns(2),
        ]);
    }

    /**
     * Recalculate totals dari item repeater.
     */
    protected static function recalcTotals(Get $get, Set $set): void
    {
        $items    = $get('orderItems') ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += (float) ($item['subtotal'] ?? 0);
        }

        $discount = (float) ($get('discount_amount') ?? 0);
        $total    = $subtotal - $discount;
        $dpPct    = (float) ($get('dp_percentage') ?? 50);
        $dp       = round($total * ($dpPct / 100), 2);

        $set('subtotal',         $subtotal);
        $set('total_amount',     $total);
        $set('dp_amount',        $dp);
        $set('remaining_amount', $total - $dp);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('No. Pesanan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Telepon')
                    ->searchable(),

                Tables\Columns\TextColumn::make('event_date')
                    ->label('Tgl. Acara')
                    ->formatStateUsing(function ($state, $record) {
                        $date = \Carbon\Carbon::parse($state)->format('d M Y');
                        $time = $record->event_time ? \Carbon\Carbon::parse($record->event_time)->format('H:i') : '';
                        return trim($date . ' ' . $time);
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Menu')
                    ->getStateUsing(function ($record) {
                        $count = $record->orderItems->count();
                        return $count . ' Menu';
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method_display')
                    ->label('Metode Pembayaran')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $method = $record->payments()->latest()->first()?->payment_method;
                        if ($method === 'cash') return 'Tunai';
                        if ($method === 'transfer' && $record->dp_percentage >= 100) return 'Transfer Lunas';
                        if ($method === 'transfer') return 'Transfer DP (DP: Rp ' . number_format($record->dp_amount, 0, ',', '.') . ' | Sisa: Rp ' . number_format($record->remaining_amount, 0, ',', '.') . ')';
                        return 'Belum Ditentukan';
                    })
                    ->color(function ($state, $record) {
                        $method = $record->payments()->latest()->first()?->payment_method;
                        if ($method === 'cash') return 'success';
                        if ($method === 'transfer' && $record->dp_percentage >= 100) return 'info';
                        if ($method === 'transfer') return 'warning';
                        return 'gray';
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'   => 'Menunggu DP',
                        'dp_paid'   => 'DP Dibayar',
                        'confirmed' => 'Dikonfirmasi',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ]),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options([
                        'unpaid'     => 'Belum Bayar',
                        'dp_paid'    => 'DP Lunas',
                        'fully_paid' => 'Lunas',
                    ]),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),

                \Filament\Actions\Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->modalHeading('Verifikasi Pembayaran')
                    ->visible(fn (Order $record) => $record->status === 'pending')
                    ->form(function(Order $record) {
                        $payment = $record->payments()->latest()->first();
                        $isCash = $payment?->payment_method === 'cash';

                        $components = [
                            \Filament\Forms\Components\Placeholder::make('info')
                                ->label('Metode Pembayaran')
                                ->content($isCash ? 'Tunai (Bayar Penuh di Tempat)' : 'Transfer Bank'),
                            
                            \Filament\Forms\Components\Placeholder::make('amount')
                                ->label('Jumlah Pembayaran yang Dilaporkan')
                                ->content('Rp ' . number_format($payment?->amount ?? 0, 0, ',', '.')),
                                
                            \Filament\Forms\Components\Placeholder::make('items_summary')
                                ->label('Detail Pesanan')
                                ->content(new \Illuminate\Support\HtmlString(
                                    "<table style='width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9em;'>
                                        <thead>
                                            <tr style='border-bottom: 2px solid #e5e7eb; text-align: left;'>
                                                <th style='padding: 8px 4px;'>Menu</th>
                                                <th style='padding: 8px 4px; width: 60px;'>Porsi</th>
                                                <th style='padding: 8px 4px;'>Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody>" . 
                                    $record->orderItems->map(function($item) {
                                        $notes = $item->notes ?: '-';
                                        return "<tr style='border-bottom: 1px solid #e5e7eb;'>
                                                    <td style='padding: 8px 4px;'>{$item->menu_name}</td>
                                                    <td style='padding: 8px 4px;'>{$item->quantity}</td>
                                                    <td style='padding: 8px 4px; color: #6b7280;'>{$notes}</td>
                                                </tr>";
                                    })->implode('') .
                                    "</tbody></table>"
                                )),
                        ];

                        if (!$isCash && $payment?->proof_image) {
                            $imageUrl = asset('storage/' . $payment->proof_image);
                            $components[] = \Filament\Forms\Components\Placeholder::make('proof')
                                ->label('Bukti Transfer')
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<div style="text-align:center;">' .
                                    '<img src="' . $imageUrl . '" onclick="document.getElementById(\'verifyLightbox\').style.display=\'flex\'" style="max-width:100%; max-height:350px; object-fit:contain; border-radius:8px; border:1px solid #ddd; cursor:zoom-in; display:block; margin:0 auto;" />' .
                                    '<div style="margin-top:10px; display:flex; gap:8px; justify-content:center;">' .
                                    '<button type="button" onclick="document.getElementById(\'verifyLightbox\').style.display=\'flex\'" style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; background:rgb(59 130 246); color:white; border-radius:8px; font-size:0.8rem; font-weight:600; border:none; cursor:pointer;">' .
                                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" /></svg>' .
                                    'Lihat Penuh</button>' .
                                    '<a href="' . $imageUrl . '" download style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; background:rgb(16 185 129); color:white; border-radius:8px; font-size:0.8rem; font-weight:600; text-decoration:none; cursor:pointer;">' .
                                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>' .
                                    'Download</a>' .
                                    '</div>' .
                                    '</div>' .
                                    '<div id="verifyLightbox" onclick="if(event.target===this) this.style.display=\'none\'" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.85); z-index:999999; align-items:center; justify-content:center; padding:2rem; cursor:zoom-out;">' .
                                    '<div style="position:relative; max-width:90vw; max-height:90vh; text-align:center;">' .
                                    '<img src="' . $imageUrl . '" style="max-width:90vw; max-height:85vh; object-fit:contain; border-radius:8px; box-shadow:0 20px 60px rgba(0,0,0,0.5);" />' .
                                    '<div style="display:flex; gap:8px; justify-content:center; margin-top:16px;">' .
                                    '<a href="' . $imageUrl . '" download onclick="event.stopPropagation()" style="display:inline-flex; align-items:center; gap:6px; padding:8px 20px; font-size:0.85rem; font-weight:600; border-radius:8px; background:rgb(16 185 129); color:white; text-decoration:none; cursor:pointer;">' .
                                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>' .
                                    'Download</a>' .
                                    '<button type="button" onclick="document.getElementById(\'verifyLightbox\').style.display=\'none\'" style="display:inline-flex; align-items:center; gap:6px; padding:8px 20px; font-size:0.85rem; font-weight:600; border-radius:8px; background:rgba(255,255,255,0.15); color:white; border:1px solid rgba(255,255,255,0.3); cursor:pointer;">' .
                                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>' .
                                    'Tutup</button>' .
                                    '</div></div></div>'
                                ));
                        } elseif (!$isCash) {
                            $components[] = \Filament\Forms\Components\Placeholder::make('proof_missing')
                                ->label('Bukti Transfer')
                                ->content(new \Illuminate\Support\HtmlString('<span style="color:red;">Belum ada bukti transfer yang diunggah.</span>'));
                        }

                        $components[] = \Filament\Forms\Components\Select::make('action')
                            ->label('Keputusan')
                            ->options([
                                'approve' => 'Terima Pembayaran',
                                'reject' => 'Tolak Pembayaran',
                            ])
                            ->required()
                            ->live();

                        $components[] = \Filament\Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->visible(fn ($get) => $get('action') === 'reject')
                            ->required(fn ($get) => $get('action') === 'reject');

                        return $components;
                    })
                    ->action(function (Order $record, array $data) {
                        $payment = $record->payments()->latest()->first();
                        
                        if ($data['action'] === 'approve') {
                            if ($payment) {
                                $payment->update(['status' => 'verified', 'verified_by' => auth()->id(), 'verified_at' => now()]);
                            }
                            app(OrderService::class)->confirm($record);
                            Notification::make()->title('Pembayaran diverifikasi! Pesanan dikonfirmasi.')->success()->send();
                        } else {
                            if ($payment) {
                                $payment->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']]);
                            }
                            app(OrderService::class)->cancel($record, $data['rejection_reason']);
                            Notification::make()->title('Pembayaran ditolak! Pesanan dibatalkan.')->danger()->send();
                        }
                    }),

                \Filament\Actions\Action::make('complete')
                    ->label('Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Selesai')
                    ->modalDescription('Apakah Anda yakin pesanan ini sudah selesai dan siap diambil/diantar?')
                    ->visible(fn (Order $record) => in_array($record->status, ['confirmed', 'dp_paid']))
                    ->action(function (Order $record) {
                        $record->update(['status' => 'completed']);
                        Notification::make()->title('Pesanan ditandai selesai!')->success()->send();
                    }),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOrders::route('/'),
            'view'   => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
