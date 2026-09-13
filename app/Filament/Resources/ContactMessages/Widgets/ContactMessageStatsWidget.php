<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Widgets;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContactMessageStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $toplam = ContactMessage::query()->count();
        $yeni = ContactMessage::query()->where('status', ContactMessageStatus::Yeni->value)->count();
        $banaAtanan = ContactMessage::query()->where('assigned_to', auth()->id())->whereIn('status', [ContactMessageStatus::Yeni->value, ContactMessageStatus::Okundu->value])->count();
        $cozuldu = ContactMessage::query()->whereIn('status', [ContactMessageStatus::Yanitlandi->value, ContactMessageStatus::Kapandi->value])->count();

        return [
            Stat::make('Toplam Mesaj', (string) $toplam)
                ->description('Gelen iletişim & destek talepleri')
                ->descriptionIcon('heroicon-m-inbox')
                ->color('primary'),

            Stat::make('Yeni & Bekleyen', (string) $yeni)
                ->description($yeni > 0 ? 'İncelenmeyi bekleyen mesajlar' : 'Tüm mesajlar okundu')
                ->descriptionIcon('heroicon-m-envelope')
                ->color($yeni > 0 ? 'danger' : 'success'),

            Stat::make('Üzerimdeki Talepler', (string) $banaAtanan)
                ->description('Size atanan açık biletler')
                ->descriptionIcon('heroicon-m-user')
                ->color($banaAtanan > 0 ? 'warning' : 'gray'),

            Stat::make('Yanıtlanan / Çözülen', (string) $cozuldu)
                ->description('Başarıyla sonlandırılanlar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
