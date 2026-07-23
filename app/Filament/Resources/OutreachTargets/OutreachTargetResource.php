<?php

namespace App\Filament\Resources\OutreachTargets;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\OutreachTargets\Pages\EditOutreachTarget;
use App\Filament\Resources\OutreachTargets\Pages\ListOutreachTargets;
use App\Filament\Resources\OutreachTargets\Schemas\OutreachTargetForm;
use App\Filament\Resources\OutreachTargets\Tables\OutreachTargetsTable;
use App\Models\OutreachTarget;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Büyüme Ajanı "Keşif Havuzu" — keşfedilen Türk işletme adaylarını gösterir;
 * sahibin sınırdakileri (inceleme bekleyen) onaylamasını/reddetmesini sağlar.
 * Admin'e özel (RestrictsToAdmins). Keşif `php artisan growth:discover <ülke>`
 * ile beslenir; gönderim durumu RegionPolicy'den gelir (AB/TR/RU engelli).
 */
class OutreachTargetResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = OutreachTarget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): ?string
    {
        return 'Sistem & Araçlar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Keşif Havuzu';
    }

    public static function getModelLabel(): string
    {
        return 'aday';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Keşif Havuzu';
    }

    /** İnceleme bekleyen aday sayısını rozet olarak göster. */
    public static function getNavigationBadge(): ?string
    {
        return (string) (OutreachTarget::query()->where('needs_review', true)->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return OutreachTargetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OutreachTargetsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOutreachTargets::route('/'),
            'edit' => EditOutreachTarget::route('/{record}/edit'),
        ];
    }
}
