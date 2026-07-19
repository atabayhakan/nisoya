<?php

namespace App\Filament\Actions;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\FraudBlocklist;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Bir kullanıcıyı DOLANDIRICILIK nedeniyle dondurur VE parmak izini
 * (e-posta + IBAN/ödeme handle'ları) kara listeye alır — böylece aynı kişi
 * yeni bir hesapla veya aynı ödeme kanalıyla geri dönemez (bkz. FraudBlocklist).
 * Nötr "Askıya al" aksiyonundan (ToggleUserStatusAction) ayrıdır: o geri
 * dönüşlü/tarafsızdır, bu ise kalıcı parmak izi bırakır.
 */
class FreezeFraudsterAction
{
    public static function make(): Action
    {
        return Action::make('freezeFraudster')
            ->label('Dolandırıcı olarak dondur')
            ->icon('heroicon-o-shield-exclamation')
            ->color('danger')
            // Kendini hedefleyemez; zaten silinmiş hesaba uygulanmaz.
            ->visible(fn (User $record) => $record->id !== auth()->id() && $record->status !== UserStatus::Silinmis)
            ->requiresConfirmation()
            ->modalHeading('Dolandırıcı olarak dondur')
            ->modalDescription(fn (User $record) => "{$record->name} askıya alınacak, tüm aktif ilanları pasif olacak VE e-posta + ödeme bilgileri (IBAN/handle) kara listeye alınacak. Bu kişi aynı e-posta ya da ödeme kanalıyla tekrar kayıt olamayacak/ödeme linki ekleyemeyecek.")
            ->modalSubmitActionLabel('Dondur ve kara listeye al')
            ->action(function (User $record) {
                $reason = 'Dolandırıcılık nedeniyle donduruldu';

                $record->update(['status' => UserStatus::Askida]);

                $fingerprints = app(FraudBlocklist::class)
                    ->fingerprintUser($record, auth()->id(), $reason);

                activity('user')
                    ->performedOn($record)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'admin_id' => auth()->id(),
                        'fingerprints' => $fingerprints,
                    ])
                    ->log('Kullanıcı dolandırıcılık nedeniyle donduruldu (parmak izi alındı)');

                Notification::make()
                    ->title('Dolandırıcı donduruldu')
                    ->body("{$record->name} askıya alındı; {$fingerprints} parmak izi kara listeye eklendi.")
                    ->success()
                    ->send();
            });
    }
}
