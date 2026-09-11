<?php

namespace App\Filament\Resources\TelegramSohbetleri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\TelegramSohbetleri\Pages\ListTelegramSohbetleri;
use App\Filament\Resources\TelegramSohbetleri\Tables\TelegramSohbetleriTable;
use App\Models\TelegramSohbeti;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Kâhya'nın Telegram grubunda verdiği cevapların günlüğü — tam bir arşiv
 * değil, "yanlış giden oldu mu" denetimi (bkz. migration docblock'u).
 *
 * Kayıt YALNIZ App\Services\Kahya\Dis\TelegramDinleyici tarafından açılır;
 * bu ekranda oluşturma/düzenleme yok — sahibin işi OKUyup gerekirse
 * "İncelendi" işaretlemek.
 */
class TelegramSohbetleriResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = TelegramSohbeti::class;

    protected static ?string $slug = 'kahya-telegram-sohbetleri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Kâhya & Yapay Zekâ';

    protected static ?string $navigationLabel = 'Telegram Sohbetleri';

    protected static ?int $navigationSort = 9;

    public static function getModelLabel(): string
    {
        return 'Telegram sohbeti';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Telegram Sohbetleri';
    }

    /** İnceleme bekleyen (hassas kategori işaretli) satır sayısı. */
    public static function getNavigationBadge(): ?string
    {
        return (string) (TelegramSohbeti::query()->needsReview()->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return TelegramSohbetleriTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTelegramSohbetleri::route('/'),
        ];
    }
}
