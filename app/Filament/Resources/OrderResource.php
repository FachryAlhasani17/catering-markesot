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
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payments.payment_method')
                    ->label('Tipe Bayar')
                    ->badge()
                    ->formatStateUsing(function ($state, $record) {
                        $method = $record->payments()->latest()->first()?->payment_method ?? $state;
                        if ($method === 'cash') return 'Tunai';
                        if ($method === 'bank' && $record->dp_percentage >= 100) return 'Transfer Lunas';
                        if ($method === 'bank') return 'DP Transfer';
                        return $method;
                    })
                    ->color(function ($state, $record) {
                        $method = $record->payments()->latest()->first()?->payment_method ?? $state;
                        if ($method === 'cash') return 'success';
                        if ($method === 'bank' && $record->dp_percentage >= 100) return 'info';
                        return 'warning';
                    }),

                Tables\Columns\TextColumn::make('status')
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

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Pembayaran')
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
                \Filament\Actions\EditAction::make(),

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
                        ];

                        if (!$isCash && $payment?->proof_image) {
                            $components[] = \Filament\Forms\Components\Placeholder::make('proof')
                                ->label('Bukti Transfer')
                                ->content(new \Illuminate\Support\HtmlString('<img src="' . asset('storage/' . $payment->proof_image) . '" style="max-width:100%; border-radius:8px; border:1px solid #ddd;" />'));
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
            'create' => Pages\CreateOrder::route('/create'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
            'view'   => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
