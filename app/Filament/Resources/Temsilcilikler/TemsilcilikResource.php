<?php

namespace App\Filament\Resources\Temsilcilikler;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\Temsilcilikler\Pages\CreateTemsilcilik;
use App\Filament\Resources\Temsilcilikler\Pages\EditTemsilcilik;
use App\Filament\Resources\Temsilcilikler\Pages\ListTemsilcilikler;
use App\Models\Country;
use App\Models\Temsilcilik;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Ülke Rehberi — dış temsilcilikler (büyükelçilik/başkonsolosluk).
 *
 * Slug URL'nin ikinci segmentidir (/de/koeln — tasarım K2); değiştirmek
 * yayındaki linkleri kırar, o yüzden düzenlemede kilitli değil ama uyarılı.
 */
class TemsilcilikResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = Temsilcilik::class;

    // Filament'in İngilizce çoğullaştırıcısı Türkçe addan saçma slug türetir
    // (bkz. kahya-hafiza dersi) — sabitle.
    protected static ?string $slug = 'temsilcilikler';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = 'Ülke Rehberi';

    protected static ?string $navigationLabel = 'Temsilcilikler';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'temsilcilik';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Temsilcilikler';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kimlik')
                ->columns(2)
                ->schema([
                    Select::make('country_code')
                        ->label('Ülke')
                        ->options(fn () => Country::query()->orderBy('sort_order')->pluck('name_tr', 'code'))
                        ->required()
                        ->native(false)
                        ->searchable(),
                    Select::make('tur')
                        ->label('Tür')
                        ->options([
                            Temsilcilik::TUR_BUYUKELCILIK => 'Büyükelçilik',
                            Temsilcilik::TUR_BASKONSOLOSLUK => 'Başkonsolosluk',
                        ])
                        ->default(Temsilcilik::TUR_BASKONSOLOSLUK)
                        ->required(),
                    TextInput::make('ad')
                        ->label('Ad')
                        ->placeholder('örn. Köln Başkonsolosluğu')
                        ->required()
                        ->maxLength(190)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (blank($get('slug')) && filled($get('sehir'))) {
                                $set('slug', Str::slug((string) $get('sehir')));
                            }
                        }),
                    TextInput::make('sehir')
                        ->label('Şehir')
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Kısa ad (URL)')
                        ->required()
                        ->maxLength(80)
                        ->helperText('Adres: nisoya.com/de/kısa-ad — yayına girdikten sonra değiştirmek linkleri kırar.'),
                ]),

            Section::make('İletişim & konum')
                ->columns(2)
                ->schema([
                    TextInput::make('adres')->label('Adres')->maxLength(500)->columnSpanFull(),
                    TextInput::make('resmi_url')->label('Resmî site adresi')->url()->maxLength(300)->columnSpanFull(),
                    TextInput::make('latitude')->label('Enlem (ops.)')->numeric(),
                    TextInput::make('longitude')->label('Boylam (ops.)')->numeric(),
                ]),

            Section::make('Yayın')
                ->columns(2)
                ->schema([
                    Toggle::make('is_active')->label('Aktif (rehberde göster)')->default(true),
                    TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')->label('Temsilcilik')->searchable()->sortable(),
                TextColumn::make('country_code')->label('Ülke')->badge(),
                TextColumn::make('sehir')->label('Şehir'),
                TextColumn::make('slug')->label('URL')->formatStateUsing(fn (Temsilcilik $r): string => '/'.strtolower($r->country_code).'/'.$r->slug),
                TextColumn::make('islemler_count')->label('İşlem')->counts('islemler'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->filters([
                SelectFilter::make('country_code')
                    ->label('Ülke')
                    ->options(fn () => Country::query()->orderBy('sort_order')->pluck('name_tr', 'code')),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTemsilcilikler::route('/'),
            'create' => CreateTemsilcilik::route('/create'),
            'edit' => EditTemsilcilik::route('/{record}/edit'),
        ];
    }
}
