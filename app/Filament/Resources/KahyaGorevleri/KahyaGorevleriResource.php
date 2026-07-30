<?php

namespace App\Filament\Resources\KahyaGorevleri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\KahyaGorevleri\Pages\CreateKahyaGorevi;
use App\Filament\Resources\KahyaGorevleri\Pages\EditKahyaGorevi;
use App\Filament\Resources\KahyaGorevleri\Pages\ListKahyaGorevleri;
use App\Filament\Resources\KahyaGorevleri\Schemas\KahyaGorevForm;
use App\Filament\Resources\KahyaGorevleri\Tables\KahyaGorevleriTable;
use App\Models\KahyaGorevi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Kâhya'nın görev defterinin panel yüzü (F2 — tasarım §2.3).
 *
 * Görevler çoğunlukla sohbetten yönetilir (gorev-ac / gorev-guncelle);
 * bu ekran sahibin kuşbakışı görünümü: hangi misyon nerede, ne kadar
 * zamandır hareketsiz. Elle görev açmak da serbest — Kâhya açık görevleri
 * her sohbetinde görür, kim açtıysa.
 */
class KahyaGorevleriResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = KahyaGorevi::class;

    protected static ?string $slug = 'kahya-gorevleri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Kâhya & Yapay Zekâ';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return 'Kâhya Görevleri';
    }

    public static function getModelLabel(): string
    {
        return 'görev';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kâhya Görevleri';
    }

    public static function form(Schema $schema): Schema
    {
        return KahyaGorevForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KahyaGorevleriTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKahyaGorevleri::route('/'),
            'create' => CreateKahyaGorevi::route('/create'),
            'edit' => EditKahyaGorevi::route('/{record}/edit'),
        ];
    }
}
