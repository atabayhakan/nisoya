<?php

namespace App\Filament\Actions;

use App\Enums\UserStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Spatie\Activitylog\Models\Activity;

/**
 * Tek bir kullanıcıyı askıya alır (suspend) veya aktifleştirir.
 * Banlanan kullanıcıların aktif ilanları otomatik pasif yapılır (UserObserver).
 */
class ToggleUserStatusAction
{
    public static function make(): Action
    {
        return Action::make('toggleStatus')
            ->label(fn (User $record) => $record->status === UserStatus::Aktif ? 'Askıya al' : 'Aktifleştir')
            ->icon(fn (User $record) => $record->status === UserStatus::Aktif ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
            ->color(fn (User $record) => $record->status === UserStatus::Aktif ? 'warning' : 'success')
            ->requiresConfirmation()
            ->modalHeading(fn (User $record) => $record->status === UserStatus::Aktif
                ? 'Kullanıcıyı askıya al'
                : 'Kullanıcıyı aktifleştir')
            ->modalDescription(fn (User $record) => $record->status === UserStatus::Aktif
                ? "{$record->name} kullanıcısının tüm aktif ilanları pasif yapılacak ve yeni ilan vermesi geçici olarak engellenecek."
                : "{$record->name} tekrar ilan verebilir ve giriş yapabilir hale gelecek.")
            ->modalSubmitActionLabel(fn (User $record) => $record->status === UserStatus::Aktif ? 'Askıya al' : 'Aktifleştir')
            ->action(function (User $record) {
                $oldStatus = $record->status;
                $newStatus = $oldStatus === UserStatus::Aktif
                    ? UserStatus::Askida
                    : UserStatus::Aktif;

                $record->update(['status' => $newStatus]);

                // Activity log: admin işlemini kaydet (Spatie otomatik yazmaz çünkü
                // admin panel aksiyonu User model updated event'i tetikler ama causer
                // olarak auth admin atanmalı).
                activity('user')
                    ->performedOn($record)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'old_status' => $oldStatus->value,
                        'new_status' => $newStatus->value,
                        'admin_id' => auth()->id(),
                    ])
                    ->log($newStatus === UserStatus::Askida ? 'Kullanıcı askıya alındı' : 'Kullanıcı aktifleştirildi');

                Notification::make()
                    ->title($newStatus === UserStatus::Askida ? 'Kullanıcı askıya alındı' : 'Kullanıcı aktifleştirildi')
                    ->body("{$record->name} → {$newStatus->getLabel()}")
                    ->success()
                    ->send();
            });
    }
}
