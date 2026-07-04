<?php

namespace App\Filament\Actions;

use App\Enums\UserStatus;
use App\Models\User;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

/**
 * Toplu kullanıcı durum değiştirme. Filament toolbar'ında görünür.
 */
class BulkToggleUserStatusAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('bulkToggleStatus')
            ->label('Durumu değiştir')
            ->icon('heroicon-o-adjustments-horizontal')
            ->form([
                Select::make('status')
                    ->label('Yeni durum')
                    ->options([
                        UserStatus::Aktif->value => UserStatus::Aktif->getLabel(),
                        UserStatus::Askida->value => UserStatus::Askida->getLabel(),
                        UserStatus::Silinmis->value => UserStatus::Silinmis->getLabel(),
                    ])
                    ->required(),
            ])
            ->requiresConfirmation()
            ->modalHeading('Toplu kullanıcı durumu değiştir')
            ->modalDescription('Seçili kullanıcıların durumları güncellenecek.')
            ->action(function (array $data, $records) {
                $count = 0;
                foreach ($records as $record) {
                    /** @var User $record */
                    if ($record->update(['status' => $data['status']])) {
                        $count++;
                    }
                }

                Notification::make()
                    ->title("{$count} kullanıcı güncellendi")
                    ->body('Yeni durum: '.UserStatus::from($data['status'])->getLabel())
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
