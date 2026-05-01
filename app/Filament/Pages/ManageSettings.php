<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

class ManageSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Pengaturan';
    protected static ?string $title = 'Pengaturan Sistem';
    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';
    
    public array $data = [];

    // State untuk masing-masing seksi edit
    public array $editModes = [
        'dp'      => false,
        'company' => false,
        'bank'    => false,
        'landing' => false,
        'admin'   => false,
    ];

    public function mount(): void
    {
        $settings = Setting::all()->keyBy('key');

        $rawTokens = $settings->get('admin_tokens')?->value ?? '[]';
        $decoded = json_decode($rawTokens, true) ?? [];

        $adminTokensData = [];
        foreach ($decoded as $entry) {
            if (is_string($entry)) {
                $adminTokensData[] = [
                    'password' => $entry,
                    'token'    => strtoupper(Str::random(8)),
                ];
            } elseif (is_array($entry) && isset($entry['password'])) {
                $adminTokensData[] = [
                    'password' => $entry['password'],
                    'token'    => $entry['token'] ?? strtoupper(Str::random(8)),
                ];
            }
        }

        if (empty($adminTokensData)) {
            $adminTokensData[] = [
                'password' => 'admin123',
                'token'    => strtoupper(Str::random(8)),
            ];
        }

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
            'best_seller_count' => $settings->get('best_seller_count')?->value ?? 1,
            'admin_tokens'     => $adminTokensData,
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
            // Footer dihapus, tombol simpan pindah ke masing-masing seksi
        ]);
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                // ─── 1. KONFIGURASI DP ───
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
                            ->disabled(fn () => empty($this->editModes['dp']))
                            ->helperText('Pesanan lama tidak terpengaruh perubahan ini.'),
                        
                        $this->makeActionButtons('dp', 'Konfigurasi DP'),
                    ])->columns(1),

                // ─── 2. INFORMASI PERUSAHAAN ───
                \Filament\Schemas\Components\Section::make('Informasi Perusahaan')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('company_name')
                            ->label('Nama Perusahaan')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn () => empty($this->editModes['company'])),

                        \Filament\Forms\Components\TextInput::make('company_phone')
                            ->label('No. Telepon / WhatsApp')
                            ->tel()
                            ->maxLength(20)
                            ->disabled(fn () => empty($this->editModes['company'])),

                        \Filament\Forms\Components\Textarea::make('company_address')
                            ->label('Alamat Lengkap')
                            ->rows(3)
                            ->columnSpanFull()
                            ->disabled(fn () => empty($this->editModes['company'])),

                        $this->makeActionButtons('company', 'Informasi Perusahaan'),
                    ])->columns(2),

                // ─── 3. REKENING BANK ───
                \Filament\Schemas\Components\Section::make('Rekening Bank')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('bank_name')
                            ->label('Nama Bank')
                            ->maxLength(100)
                            ->disabled(fn () => empty($this->editModes['bank'])),

                        \Filament\Forms\Components\TextInput::make('account_number')
                            ->label('Nomor Rekening')
                            ->maxLength(50)
                            ->disabled(fn () => empty($this->editModes['bank'])),

                        \Filament\Forms\Components\TextInput::make('account_name')
                            ->label('Nama Pemilik Rekening')
                            ->maxLength(255)
                            ->disabled(fn () => empty($this->editModes['bank'])),

                        $this->makeActionButtons('bank', 'Rekening Bank', 3),
                    ])->columns(3),

                // ─── 4. TAMPILAN LANDING PAGE ───
                \Filament\Schemas\Components\Section::make('Tampilan Landing Page')
                    ->icon('heroicon-o-computer-desktop')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('best_seller_count')
                            ->label('Jumlah Menu Best Seller')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(1)
                            ->disabled(fn () => empty($this->editModes['landing']))
                            ->helperText('Tentukan berapa banyak menu yang akan ditampilkan sebagai Best Seller.'),

                        $this->makeActionButtons('landing', 'Tampilan Landing Page'),
                    ])->columns(1),

                // ─── 5. PASSWORD REGISTRASI ADMIN ───
                \Filament\Schemas\Components\Section::make('Password Registrasi Admin')
                    ->description('Daftar password khusus dan token verifikasi untuk mendaftar sebagai admin. Token akan di-generate otomatis. Minimal harus ada 1 password.')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Repeater::make('admin_tokens')
                            ->label('Daftar Password Admin')
                            ->disabled(fn () => empty($this->editModes['admin']))
                            ->schema([
                                TextInput::make('password')
                                    ->label('Password Admin')
                                    ->required()
                                    ->password()
                                    ->revealable()
                                    ->placeholder('Contoh: admin123')
                                    ->minLength(4)
                                    ->columnSpan(1),

                                TextInput::make('token')
                                    ->label('Token (Auto-generate)')
                                    ->password()
                                    ->revealable()
                                    ->disabled()
                                    ->dehydrated()
                                    ->placeholder('Akan di-generate otomatis saat disimpan')
                                    ->helperText('Token ini yang dimasukkan user saat popup verifikasi.')
                                    ->columnSpan(1),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Password Admin')
                            ->reorderable(false)
                            ->deleteAction(fn ($action) => $action->requiresConfirmation()
                                ->before(function (array $state, $action) {
                                    if (count($state) <= 1) {
                                        Notification::make()
                                            ->title('Anda harus memberikan password setidaknya 1 password untuk admin')
                                            ->danger()
                                            ->send();
                                        $action->cancel();
                                    }
                                })
                            )
                            ->helperText('Minimal 1 password admin harus tersedia. Token akan otomatis di-generate saat disimpan.'),
                        
                        \Filament\Schemas\Components\Actions::make([
                            \Filament\Actions\Action::make('edit_admin')
                                ->label('Edit')
                                ->icon('heroicon-m-pencil-square')
                                ->color('gray')
                                ->action(fn () => $this->editModes['admin'] = true)
                                ->hidden(fn () => !empty($this->editModes['admin'])),

                            \Filament\Actions\Action::make('save_admin')
                                ->label('Simpan')
                                ->color('primary')
                                ->icon('heroicon-m-check')
                                ->action('save')
                                ->hidden(fn () => empty($this->editModes['admin'])),

                            \Filament\Actions\Action::make('cancel_admin')
                                ->label('Batal')
                                ->color('danger')
                                ->icon('heroicon-m-x-mark')
                                ->requiresConfirmation()
                                ->modalHeading('Batal Mengubah Password')
                                ->modalDescription('Apakah anda tidak jadi merubah atau menambahkan password admin baru?')
                                ->modalSubmitActionLabel('Ya, Batal')
                                ->action(function () {
                                    $this->editModes['admin'] = false;
                                    $this->form->fill($this->data);
                                })
                                ->hidden(fn () => empty($this->editModes['admin'])),
                        ])->columnSpanFull(),
                    ])->columns(1),
            ])
            ->statePath('data');
    }

    /**
     * Helper untuk membuat tombol Edit/Simpan/Batal per Section
     */
    protected function makeActionButtons(string $key, string $label, int $columnSpan = 1): \Filament\Schemas\Components\Actions
    {
        return \Filament\Schemas\Components\Actions::make([
            \Filament\Actions\Action::make("edit_{$key}")
                ->label('Edit')
                ->icon('heroicon-m-pencil-square')
                ->color('gray')
                ->action(fn () => $this->editModes[$key] = true)
                ->hidden(fn () => !empty($this->editModes[$key])),

            \Filament\Actions\Action::make("save_{$key}")
                ->label('Simpan')
                ->color('primary')
                ->icon('heroicon-m-check')
                ->action('save')
                ->hidden(fn () => empty($this->editModes[$key])),

            \Filament\Actions\Action::make("cancel_{$key}")
                ->label('Batal')
                ->color('danger')
                ->icon('heroicon-m-x-mark')
                ->action(function () use ($key) {
                    $this->editModes[$key] = false;
                    $this->form->fill($this->data);
                })
                ->hidden(fn () => empty($this->editModes[$key])),
        ])->columnSpanFull();
    }

    public function save(): void
    {
        // getState() otomatis HANYA akan mengambil value dari input yang aktif (sedang diedit)
        $data = $this->form->getState();

        // Proses khusus jika sedang mengedit admin_tokens
        if (isset($data['admin_tokens'])) {
            $rawOld = Setting::where('key', 'admin_tokens')->value('value') ?? '[]';
            $oldEntries = json_decode($rawOld, true) ?? [];
            $oldPasswordTokenMap = [];
            foreach ($oldEntries as $entry) {
                if (is_array($entry) && isset($entry['password'], $entry['token'])) {
                    $oldPasswordTokenMap[$entry['password']] = $entry['token'];
                }
            }

            $newEntries = [];
            foreach ($data['admin_tokens'] as $row) {
                $pwd = trim($row['password'] ?? '');
                if (!$pwd) continue;

                if (isset($oldPasswordTokenMap[$pwd])) {
                    $token = $oldPasswordTokenMap[$pwd];
                } else {
                    $token = strtoupper(Str::random(8));
                }

                $newEntries[] = [
                    'password' => $pwd,
                    'token'    => $token,
                ];
            }

            if (empty($newEntries)) {
                Notification::make()
                    ->title('Minimal 1 password admin harus tersedia!')
                    ->danger()
                    ->send();
                return;
            }

            Setting::updateOrCreate(
                ['key' => 'admin_tokens'],
                ['value' => json_encode($newEntries), 'type' => 'json', 'label' => 'Password Registrasi Admin']
            );
            Cache::forget('setting_admin_tokens');
            unset($data['admin_tokens']);
        }

        // Proses simpan untuk input lainnya (DP, Bank, dll yang sedang aktif)
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

        // Refresh data dan matikan mode edit untuk semuanya
        $this->mount();
        $this->editModes = [
            'dp'      => false,
            'company' => false,
            'bank'    => false,
            'landing' => false,
            'admin'   => false,
        ];
    }
}
