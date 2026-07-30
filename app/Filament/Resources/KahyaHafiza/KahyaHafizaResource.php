<?php

namespace App\Filament\Resources\KahyaHafiza;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\KahyaHafiza\Pages\CreateKahyaHafizasi;
use App\Filament\Resources\KahyaHafiza\Pages\EditKahyaHafizasi;
use App\Filament\Resources\KahyaHafiza\Pages\ListKahyaHafizasi;
use App\Filament\Resources\KahyaHafiza\Schemas\KahyaHafizaForm;
use App\Filament\Resources\KahyaHafiza\Tables\KahyaHafizaTable;
use App\Models\KahyaHafizasi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Kâhya'nın kalıcı hafızasının yönetim ekranı (F1 — tasarım §2.3).
 *
 * Sohbetten `hatirla`/`unut` ile yönetilen kayıtların panel yüzü: sahip
 * listeyi görür, düzenler, pasife çeker ya da KALICI siler (kalıcı silme
 * yalnız buradan — sohbetteki "unut" pasife çeker). F5'te Kâhya'nın kendi
 * çıkarımları da buraya `kahya-cikarimi` kaynağıyla düşecek; sahip saçma
 * bir çıkarımı tek bakışta ayırt edip silebilmeli — kaynak rozeti o yüzden.
 */
class KahyaHafizaResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = KahyaHafizasi::class;

    // Filament'in İngilizce çoğullaştırıcısı Türkçe model adından anlamsız
    // bir adres türetir — adres sabitlenir.
    protected static ?string $slug = 'kahya-hafiza';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmarkSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Kâhya & Yapay Zekâ';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'Kâhya Hafızası';
    }

    public static function getModelLabel(): string
    {
        return 'hafıza kaydı';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kâhya Hafızası';
    }

    public static function form(Schema $schema): Schema
    {
        return KahyaHafizaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KahyaHafizaTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKahyaHafizasi::route('/'),
            'create' => CreateKahyaHafizasi::route('/create'),
            'edit' => EditKahyaHafizasi::route('/{record}/edit'),
        ];
    }
}
