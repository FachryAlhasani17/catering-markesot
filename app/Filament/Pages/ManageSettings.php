<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class ManageSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Pengaturan';
    protected static ?string $title = 'Pengaturan Sistem';
    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';
    public array $data = [];

    public function mount(): void
    {
        $settings = Setting::all()->keyBy('key');

        $this->data = [
            'dp_percentage'    => $settings->get('dp_percentage')?->value ?? '50',
            'company_name'     => $settings->get('company_name')?->value ?? '',
            'company_phone'    => $settings->get('company_phone')?->value ?? '',
            'company_address'  => $settings->get('company_address')?->value ?? '',
            'bank_name'        => $settings->get('bank_name')?->value ?? '',
            'account_number'   => $settings->get('account_number')?->value ?? '',
            'account_name'     => $settings->get('account_name')?->value ?? '',
            'payment_qris_string' => $settings->get('payment_qris_string')?->value ?? '',
            'qr_payment_image' => $settings->get('qr_payment_image')?->value ?? '',
        ];

        $this->form->fill($this->data);
    }

    public function content(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Form::make([
                \Filament\Schemas\Components\EmbeddedSchema::make('form'),
            ])
            ->id('manage-settings-form')
            ->livewireSubmitHandler('save')
            ->footer([
                \Filament\Schemas\Components\Actions::make($this->getFormActions())
                    ->alignment('start'),
            ]),
        ]);
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Konfigurasi DP')
                    ->description('Pengaturan persentase down payment yang berlaku untuk semua pesanan baru.')
                    ->icon('heroicon-o-percent-badge')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('dp_percentage')
                            ->label('Persentase DP (%)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->helperText('Pesanan lama tidak terpengaruh perubahan ini.'),
                    ])->columns(1),

                \Filament\Schemas\Components\Section::make('Informasi Perusahaan')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('company_name')
                            ->label('Nama Perusahaan')
                            ->required()
                            ->maxLength(255),

                        \Filament\Forms\Components\TextInput::make('company_phone')
                            ->label('No. Telepon / WhatsApp')
                            ->tel()
                            ->maxLength(20),

                        \Filament\Forms\Components\Textarea::make('company_address')
                            ->label('Alamat Lengkap')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Rekening Bank')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('bank_name')
                            ->label('Nama Bank')
                            ->maxLength(100),

                        \Filament\Forms\Components\TextInput::make('account_number')
                            ->label('Nomor Rekening')
                            ->maxLength(50),

                        \Filament\Forms\Components\TextInput::make('account_name')
                            ->label('Nama Pemilik Rekening')
                            ->maxLength(255),
                    ])->columns(3),

                \Filament\Schemas\Components\Section::make('QR Code Pembayaran')
                    ->icon('heroicon-o-qr-code')
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('payment_qris_string')
                            ->label('QRIS String Text')
                            ->helperText('Masukkan teks / string QRIS di sini jika ingin generate QRIS secara dinamis.')
                            ->rows(3)
                            ->columnSpanFull(),

                        \Filament\Forms\Components\FileUpload::make('qr_payment_image')
                            ->label('Upload Gambar QRIS (Alternatif)')
                            ->helperText('Gunakan ini jika kamu hanya ingin mengupload gambar statis.')
                            ->image()
                            ->directory('settings')
                            ->visibility('public')
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
            Cache::forget("setting_{$key}");
        }

        Notification::make()
            ->title('Pengaturan berhasil disimpan!')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Simpan Pengaturan')
                ->submit('save')
                ->color('primary'),
        ];
    }
}
