<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Pembayaran';
    protected static ?string $modelLabel = 'Pembayaran';
    protected static ?string $pluralModelLabel = 'Pembayaran';
    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pembayaran')->schema([
                Select::make('order_id')
                    ->label('No. Pesanan')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->required(),

                Select::make('type')
                    ->label('Jenis Pembayaran')
                    ->options([
                        'dp'         => 'Down Payment (DP)',
                        'settlement' => 'Pelunasan',
                    ])
                    ->required(),

                TextInput::make('amount')
                    ->label('Jumlah Pembayaran')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),

                Select::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'transfer' => 'Transfer Bank',
                        'cash'     => 'Tunai',
                    ])
                    ->nullable(),
            ])->columns(2),

            Section::make('Detail Transfer')->schema([
                TextInput::make('bank_name')
                    ->label('Nama Bank')
                    ->nullable(),

                TextInput::make('account_name')
                    ->label('Nama Pengirim')
                    ->nullable(),

                DatePicker::make('transfer_date')
                    ->label('Tanggal Transfer')
                    ->native(false)
                    ->nullable(),
            ])->columns(3),

            Section::make('Bukti Pembayaran')->schema([
                FileUpload::make('proof_image')
                    ->label('Foto Bukti Transfer')
                    ->image()
                    ->imageEditor()
                    ->directory('payment-proofs')
                    ->visibility('public')
                    ->nullable()
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(2)
                    ->nullable()
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('No. Pembayaran')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('No. Pesanan')
                    ->searchable()
                    ->url(fn (Payment $record) => route('filament.admin.resources.orders.view', $record->order_id))
                    ->color('primary'),

                Tables\Columns\TextColumn::make('order.customer_name')
                    ->label('Pelanggan')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'dp'         => 'warning',
                        'settlement' => 'info',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'dp'         => 'Down Payment',
                        'settlement' => 'Pelunasan',
                        default      => $state,
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'transfer' => 'Transfer Bank',
                        'cash'     => 'Tunai',
                        default    => $state ?? '—',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'  => 'warning',
                        'verified' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending'  => 'Menunggu',
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                        default    => $state,
                    }),

                Tables\Columns\ImageColumn::make('proof_image')
                    ->label('Bukti')
                    ->circular()
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=PA&background=e5e7eb'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diterima')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'  => 'Menunggu Verifikasi',
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'dp'         => 'Down Payment',
                        'settlement' => 'Pelunasan',
                    ]),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),

                \Filament\Actions\Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi Pembayaran')
                    ->modalDescription('Konfirmasi bahwa pembayaran ini sudah diterima dan valid.')
                    ->visible(fn (Payment $record) => $record->status === 'pending')
                    ->action(function (Payment $record) {
                        $record->update([
                            'status'      => 'verified',
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);
                        Notification::make()->title('Pembayaran diverifikasi!')->success()->send();
                    }),

                \Filament\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record) => $record->status === 'pending')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->action(function (Payment $record, array $data) {
                        $record->update([
                            'status'           => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        Notification::make()->title('Pembayaran ditolak.')->danger()->send();
                    }),

                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit'   => Pages\EditPayment::route('/{record}/edit'),
            'view'   => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
