<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Setting;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Inject dp_percentage dari setting sebelum create (observer juga akan handle ini)
        if (empty($data['dp_percentage'])) {
            $data['dp_percentage'] = (float) Setting::where('key', 'dp_percentage')->value('value') ?? 50;
        }
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
