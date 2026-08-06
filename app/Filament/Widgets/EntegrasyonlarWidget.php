<?php

namespace App\Filament\Widgets;

use App\Providers\Filament\AdminPanelProvider;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dış servis yapılandırması: AdSense · Analytics · Bağış.
 *
 * ---------------------------------------------------------------------------
 * NEDEN AYRI BİR WIDGET
 *
 * Bu üçü {@see StatsOverview} içinde, üye/ilan/mesaj sayılarının yanında
 * duruyordu. Ama bunlar "bugün ne oldu" değil "kurulum doğru mu" bilgisidir:
 * yılda bir değişir, her sabah okunmaz. Panonun en üst satırında kalıcı yer
 * kaplamaları, gerçekten değişen sayıları aşağı itiyordu.
 *
 * Yeni yerleri sistem sağlığının hemen altı — "makine doğru kurulmuş mu"
 * sorusunun cevabı hep birlikte duruyor.
 *
 * Yalnız admine görünür: moderatörün AdSense yayıncı kimliğiyle ya da bağış
 * IBAN'ıyla yapabileceği bir şey yok, panosunda yer kaplamasın.
 */
class EntegrasyonlarWidget extends BaseWidget
{
    /** Sıra merdiveni {@see AdminPanelProvider} içinde. */
    protected static ?int $sort = 80;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $adsenseOk = (bool) config('services.adsense.enabled') && (bool) config('services.adsense.publisher_id');
        $analyticsOk = (bool) config('services.analytics.enabled') && (bool) config('services.analytics.measurement_id');
        $paypal = setting('bagis.paypal_me');
        $iban = setting('bagis.iban');

        return [
            Stat::make('AdSense', $adsenseOk ? 'Aktif' : 'Pasif')
                ->description($adsenseOk ? 'Yayıncı: '.config('services.adsense.publisher_id') : '.env veya admin panelden etkinleştir')
                ->descriptionIcon($adsenseOk ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->color($adsenseOk ? 'success' : 'danger'),

            Stat::make('Analytics', $analyticsOk ? 'Aktif' : 'Pasif')
                ->description($analyticsOk ? config('services.analytics.measurement_id') : 'Ölçüm ID eksik')
                ->descriptionIcon($analyticsOk ? 'heroicon-m-chart-bar' : 'heroicon-m-x-circle')
                ->color($analyticsOk ? 'success' : 'danger'),

            Stat::make('Bağış', ($paypal || $iban) ? 'Yapılandırıldı' : 'Boş')
                ->description($paypal ? 'PayPal: '.$paypal : ($iban ? 'IBAN eklendi' : 'Site Yönetimi → Bağış'))
                ->descriptionIcon(($paypal || $iban) ? 'heroicon-m-heart' : 'heroicon-m-exclamation-triangle')
                ->color(($paypal || $iban) ? 'success' : 'warning'),
        ];
    }
}
