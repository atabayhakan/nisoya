<?php

namespace App\Filament\Resources\Listings\Schemas;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\PriceUnit;
use App\Models\Country;
use App\Models\Currency;
use App\Services\Ai\MarketplaceAiAssistant;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class ListingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('İlan Bilgileri')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Üye')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->hintAction(
                                Action::make('aiKategoriOner')
                                    ->label('AI Kategori Öner')
                                    ->icon(Heroicon::OutlinedSparkles)
                                    ->action(function (callable $get, callable $set, MarketplaceAiAssistant $assistant): void {
                                        $title = (string) $get('title');
                                        $desc = (string) $get('description');
                                        if (blank($title)) {
                                            Notification::make()->title('Önce ilan başlığı giriniz')->warning()->send();

                                            return;
                                        }
                                        $oneri = $assistant->suggestCategoryAndTags($title, $desc);
                                        if ($oneri['category_id']) {
                                            $set('category_id', $oneri['category_id']);
                                            Notification::make()
                                                ->title("Kategori önerildi: {$oneri['category_name']}")
                                                ->success()
                                                ->send();
                                        } else {
                                            Notification::make()->title('Uygun kategori eşleştirilemedi')->info()->send();
                                        }
                                    })
                            ),
                        Select::make('type')
                            ->label('Tür')
                            ->options(ListingType::class)
                            ->default('hizmet')
                            ->required(),
                        TextInput::make('title')
                            ->label('Başlık')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state)))
                            ->hintAction(
                                Action::make('aiBaslikGelistir')
                                    ->label('AI Başlık')
                                    ->icon(Heroicon::OutlinedSparkles)
                                    ->action(function (callable $get, callable $set, MarketplaceAiAssistant $assistant): void {
                                        $title = (string) $get('title');
                                        if (blank($title)) {
                                            Notification::make()->title('Önce bir taslak başlık yazınız')->warning()->send();

                                            return;
                                        }
                                        $desc = (string) $get('description');
                                        $city = (string) $get('city');
                                        $sonuc = $assistant->improveListing($title, $desc, null, $city);
                                        $set('title', $sonuc['title']);
                                        $set('slug', Str::slug($sonuc['title']));
                                        Notification::make()->title('İlan başlığı güçlendirildi')->success()->send();
                                    })
                            ),
                        TextInput::make('slug')
                            ->label('Kısa ad (URL)')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Açıklama')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull()
                            ->hintAction(
                                Action::make('aiAciklamaZenginlestir')
                                    ->label('AI Açıklama')
                                    ->icon(Heroicon::OutlinedSparkles)
                                    ->action(function (callable $get, callable $set, MarketplaceAiAssistant $assistant): void {
                                        $desc = (string) $get('description');
                                        $title = (string) $get('title');
                                        if (blank($title) && blank($desc)) {
                                            Notification::make()->title('Önce başlık veya kısa açıklama yazınız')->warning()->send();

                                            return;
                                        }
                                        $city = (string) $get('city');
                                        $sonuc = $assistant->improveListing($title, $desc, null, $city);
                                        $set('description', $sonuc['description']);
                                        Notification::make()->title('İlan açıklaması zenginleştirildi')->success()->send();
                                    })
                            ),
                    ]),

                Section::make('Fiyat & Konum')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')
                            ->label('Fiyat')
                            ->numeric()
                            ->placeholder('Boş bırakırsan "görüşülür"'),
                        Select::make('currency')
                            ->label('Para birimi')
                            ->options(fn () => Currency::query()->orderBy('sort_order')->pluck('name', 'code'))
                            ->default('EUR')
                            ->required(),
                        Select::make('price_unit')
                            ->label('Fiyat birimi')
                            ->options(PriceUnit::class)
                            ->default('gorusulur')
                            ->required(),
                        Select::make('country_code')
                            ->label('Ülke')
                            ->options(fn () => Country::query()->orderBy('sort_order')->get()
                                ->mapWithKeys(fn ($c) => [$c->code => trim(($c->emoji ?? '').' '.$c->name_tr)]))
                            ->searchable(),
                        TextInput::make('city')
                            ->label('Şehir')
                            ->maxLength(255),
                        Toggle::make('is_remote')
                            ->label('Uzaktan / online verilebilir'),
                    ]),

                Section::make('Durum & Öne Çıkarma')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Durum')
                            ->options(ListingStatus::class)
                            ->default('beklemede')
                            ->required(),
                        TextInput::make('stock')
                            ->label('Stok (ürün için)')
                            ->numeric(),
                        Toggle::make('is_featured')
                            ->label('Öne çıkan'),
                        DateTimePicker::make('featured_until')
                            ->label('Öne çıkma bitiş tarihi'),
                    ]),
            ]);
    }
}
