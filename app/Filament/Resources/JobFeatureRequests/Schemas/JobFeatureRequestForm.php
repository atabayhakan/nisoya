<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobFeatureRequests\Schemas;

use App\Enums\FeatureRequestStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class JobFeatureRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('İlan ve İşveren Bilgileri')
                ->description('Öne çıkarılacak kurumsal iş ilanı ve başvuran yetkili hesabı.')
                ->schema([
                    Select::make('job_listing_id')
                        ->label('İş İlanı')
                        ->relationship('jobListing', 'title')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Öne çıkarma talep edilen kayıtlı iş ilanı.'),

                    Select::make('user_id')
                        ->label('Talep Eden İşveren / Yetkili')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Talebi ileten platform kullanıcısı.'),

                    Select::make('days')
                        ->label('Öne Çıkarma Süresi')
                        ->options([
                            7 => '7 Gün (1 Hafta)',
                            14 => '14 Gün (2 Hafta)',
                            30 => '30 Gün (1 Ay)',
                            60 => '60 Gün (2 Ay)',
                        ])
                        ->default(7)
                        ->required()
                        ->helperText('Onaylandığı takdirde ilanın vitrinde kalacağı gün sayısı.'),
                ])
                ->columns(3),

            Section::make('Moderasyon ve Vitrin Süreci')
                ->description('Yönetim onayı ve işlem zaman damgası.')
                ->schema([
                    Select::make('status')
                        ->label('Talep Durumu')
                        ->options(FeatureRequestStatus::class)
                        ->default(FeatureRequestStatus::Beklemede)
                        ->required()
                        ->helperText('Onaylandığında ilan otomatik olarak vitrinde öne çıkarılır.'),

                    DateTimePicker::make('processed_at')
                        ->label('İşlem Tarihi')
                        ->placeholder('İşlem anında otomatik atanır')
                        ->helperText('Onay veya ret verildiği zaman damgası.'),
                ])
                ->columns(2),

            Section::make('Vitrin Kuralları ve Avantajları')
                ->collapsed()
                ->schema([
                    Placeholder::make('vitrin_bilgi')
                        ->label('')
                        ->content(new HtmlString(
                            "<div class='text-xs text-gray-600 dark:text-gray-300 space-y-2'>"
                            ."<div><strong>🚀 Üst Sıra Görünürlüğü:</strong> Öne çıkarılan iş ilanları, adayların yaptığı aramalarda, kategori sayfalarında ve ana sayfadaki 'Öne Çıkan İşler' bölümünde renkli rozet ve öncelikli sıralama ile gösterilir.</div>"
                            .'<div><strong>⏱️ Süre Güvencesi:</strong> Zaten vitrinde aktif olan bir ilana yeni bir onay verildiğinde mevcut kalan süre korunur, yeni süre ile kıyaslanarak daha uzun olan geçerli kılınır.</div>'
                            .'<div><strong>🛡️ Adil Rekabet:</strong> Sadece doğrulanmış kurumsal hesapların ve aktif iş ilanlarının öne çıkarılması onaylanmalıdır.</div>'
                            .'</div>'
                        )),
                ]),
        ]);
    }
}
