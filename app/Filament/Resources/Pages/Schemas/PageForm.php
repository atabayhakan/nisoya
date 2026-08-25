<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PageStatus;
use App\Filament\Support\ContentBlocks;
use App\Models\Page;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // SSS 2026-08-25: içerik SssSorusu'ya taşındı, blocks'a burada
            // yazılan HİÇBİR ŞEY /sss'te görünmez (PageController::show()
            // slug='sss' için bu view'ı hiç render etmiyor) — bu depoda 5 kez
            // düşülen "yanlış yeri düzenle, sessizce hiçbir şey olmasın"
            // tuzağına 6.'sını eklememek için erken ve net uyarı.
            Placeholder::make('sss_uyarisi')
                ->hiddenLabel()
                ->content(new HtmlString(
                    '<div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">'
                    .'⚠️ Bu sayfanın içeriği artık aşağıdaki "İçerik blokları" alanından DEĞİL, <strong>SSS</strong> panelinden yönetiliyor. '
                    .'Buradan yalnız başlık, footer görünürlüğü ve SEO açıklaması düzenlenir.'
                    .'</div>'
                ))
                ->visible(fn (?Page $record) => $record?->slug === 'sss'),

            Section::make('Sayfa bilgileri')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Başlık')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Kısa ad (URL)')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->rule(Rule::notIn(Page::RESERVED_SLUGS))
                        // K5 (ülke-adaptif rehber tasarımı): 2 harfli yollar ülke
                        // rehberine ayrıldı (/de, /kg...) — 2 harfli bir CMS slug'ı
                        // rehber rotasının gölgesinde kalır ve asla açılamazdı.
                        ->notRegex('/^[a-z]{2}$/')
                        ->validationMessages(['not_regex' => '2 harfli kısa adlar ülke rehberi adresleri (/de gibi) için ayrılmıştır.'])
                        ->helperText('Adres: nisoya.com/kısa-ad — sadece küçük harf, rakam ve tire. 2 harfli adlar ülke rehberine ayrılmıştır.'),
                    Select::make('status')
                        ->label('Durum')
                        ->options(PageStatus::class)
                        ->default(PageStatus::Taslak->value)
                        ->required(),
                    DateTimePicker::make('publish_at')
                        ->label('Yayın zamanı (ileri tarih)')
                        ->seconds(false)
                        ->native(false)
                        ->helperText('Boş = hemen yayında. İleri bir tarih seçersen, durum "Yayında" olsa bile o ana kadar ziyaretçilere görünmez; zamanı gelince otomatik yayınlanır.'),
                    TextInput::make('sort_order')
                        ->label('Sıra (footer)')
                        ->numeric()
                        ->default(0),
                    Toggle::make('show_in_footer')
                        ->label('Footer menüsünde göster')
                        ->columnSpanFull(),
                    Textarea::make('meta_description')
                        ->label('SEO açıklaması (meta)')
                        ->maxLength(255)
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('İçerik blokları')
                ->description('Sayfayı bloklarla oluştur: başlık, metin, görsel, iki sütun, çağrı ve ayraç ekleyebilirsin.')
                ->schema([
                    Builder::make('blocks')
                        ->label('')
                        ->collapsible()
                        ->cloneable()
                        ->blockNumbers(false)
                        ->blocks(ContentBlocks::schema()),
                ]),
        ]);
    }
}
