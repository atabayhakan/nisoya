<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Pages;

use App\Enums\AccountType;
use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['slug'] ?? null) && filled($data['name'] ?? null)) {
            $base = Str::slug($data['name']) ?: 'sirket';
            $slug = $base;
            $i = 1;
            while (Company::query()->where('slug', $slug)->exists()) {
                $slug = "{$base}-".(++$i);
            }
            $data['slug'] = $slug;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Company $record */
        $record = $this->record;

        if ($record->user && $record->user->account_type !== AccountType::Kurumsal) {
            $record->user->update(['account_type' => AccountType::Kurumsal]);
        }
    }
}
