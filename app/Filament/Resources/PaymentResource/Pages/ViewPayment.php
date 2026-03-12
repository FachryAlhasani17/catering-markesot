<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use Filament\Actions;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('verify')
                ->label('Verifikasi')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'pending')
                ->action(function () {
                    $this->record->update([
                        'status'      => 'verified',
                        'verified_by' => auth()->id(),
                        'verified_at' => now(),
                    ]);
                    $this->refreshFormData(['status', 'verified_at']);
                    Notification::make()->title('Pembayaran berhasil diverifikasi!')->success()->send();
                }),

            Actions\Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'pending')
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_reason')
                        ->label('Alasan Penolakan')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status'           => 'rejected',
                        'rejection_reason' => $data['rejection_reason'],
                    ]);
                    $this->refreshFormData(['status', 'rejection_reason']);
                    Notification::make()->title('Pembayaran ditolak.')->danger()->send();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Info Pembayaran')->schema([
                Infolists\Components\TextEntry::make('payment_number')
                    ->label('No. Pembayaran')
                    ->weight('bold')
                    ->copyable(),

                Infolists\Components\TextEntry::make('order.order_number')
                    ->label('No. Pesanan')
                    ->url(fn (Payment $record) => route('filament.admin.resources.orders.view', $record->order_id))
                    ->color('primary'),

                Infolists\Components\TextEntry::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn ($state) => $state === 'dp' ? 'warning' : 'info')
                    ->formatStateUsing(fn ($state) => $state === 'dp' ? 'Down Payment' : 'Pelunasan'),

                Infolists\Components\TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'  => 'warning',
                        'verified' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending'  => 'Menunggu Verifikasi',
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                        default    => $state,
                    }),
            ])->columns(4),

            Section::make('Detail Pembayaran')->schema([
                Infolists\Components\TextEntry::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->weight('bold'),

                Infolists\Components\TextEntry::make('payment_method')
                    ->label('Metode')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'transfer' => 'Transfer Bank',
                        'qris'     => 'QRIS',
                        'cash'     => 'Tunai',
                        default    => $state ?? '—',
                    }),

                Infolists\Components\TextEntry::make('bank_name')
                    ->label('Bank')
                    ->placeholder('—'),

                Infolists\Components\TextEntry::make('account_name')
                    ->label('Nama Pengirim')
                    ->placeholder('—'),

                Infolists\Components\TextEntry::make('transfer_date')
                    ->label('Tgl. Transfer')
                    ->date('d M Y')
                    ->placeholder('—'),

                Infolists\Components\TextEntry::make('notes')
                    ->label('Catatan')
                    ->placeholder('—'),
            ])->columns(3),

            Section::make('Bukti Pembayaran')->schema([
                Infolists\Components\ImageEntry::make('proof_image')
                    ->label('Foto Bukti')
                    ->height(300)
                    ->columnSpanFull(),
            ]),

            Section::make('Verifikasi')->schema([
                Infolists\Components\TextEntry::make('verifiedBy.name')
                    ->label('Diverifikasi Oleh')
                    ->placeholder('Belum diverifikasi'),

                Infolists\Components\TextEntry::make('verified_at')
                    ->label('Waktu Verifikasi')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—'),

                Infolists\Components\TextEntry::make('rejection_reason')
                    ->label('Alasan Penolakan')
                    ->placeholder('—')
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}
