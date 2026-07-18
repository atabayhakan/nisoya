<?php

namespace App\Filament\Concerns;

/**
 * Bir Filament Resource'u (veya Page'i) yalnızca Admin'e açar — Moderatöre
 * kapatır. `canAccess()` false dönünce kaynak navigasyonda görünmez VE tüm
 * sayfaları 403 verir (tek noktadan tam kilit).
 *
 * KRİTİK gerekçe: Moderatör panele erişebiliyor (UserRole::canAccessAdminPanel
 * Moderator+Admin). Bir Resource'un policy'si/kaynak-yetkisi yoksa Filament
 * varsayılanı "erişebilir" olduğu için Moderatör TÜM kaynaklara düşerdi.
 * Örn. Kullanıcılar kaynağında bir Admin'in kaydını açıp parolasını
 * değiştirip hesabı ele geçirebiliyordu (alan-seviyesi rol kilidi yetmiyor —
 * kaynak-seviyesi yetki şart). Bu trait hesap/site-konfigürasyonu/monetizasyon/
 * taksonomi kaynaklarını Admin'e kilitler. İçerik moderasyonu kaynakları
 * (ilan/değerlendirme/rapor/story/şirket/mesaj) trait'i KULLANMAZ → Moderatöre
 * açık kalır.
 *
 * Bkz. [[nisoya-guvenlik-transa]].
 */
trait RestrictsToAdmins
{
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
