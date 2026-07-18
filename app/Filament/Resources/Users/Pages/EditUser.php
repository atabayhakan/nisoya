<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Kalıcı silme yalnızca Admin'e açık ve kişi kendini silemez
            // (kendini panelden kilitleme koruması).
            DeleteAction::make()
                ->visible(fn (): bool => (auth()->user()?->isAdmin() ?? false)
                    && $this->getRecord()->getKey() !== auth()->id()),
        ];
    }
}
