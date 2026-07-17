<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Enums\ContactMessageStatus;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContactMessage extends EditRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /** Mesaj bir yönetici tarafından açılınca "yeni" ise otomatik "okundu"ya çekilir. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if ($record instanceof ContactMessage && $record->status === ContactMessageStatus::Yeni) {
            $record->update(['status' => ContactMessageStatus::Okundu]);
            $data['status'] = ContactMessageStatus::Okundu->value;
        }

        return $data;
    }
}
