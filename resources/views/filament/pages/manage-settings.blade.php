<x-filament-panels::page>
    <x-filament::form wire:submit="save">
        {{ $this->form }}

        <x-filament::button type="submit" color="primary" class="mt-4">
            Simpan Pengaturan
        </x-filament::button>
    </x-filament::form>
</x-filament-panels::page>
