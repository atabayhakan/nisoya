<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyReviews\Schemas;

use App\Enums\ReviewStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Değerlendirme Bilgileri')
                    ->description('Değerlendirilen işveren şirket, aday ve puanlama detayları.')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->label('Şirket')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('reviewer_id')
                            ->label('Değerlendiren Aday / Üye')
                            ->relationship('reviewer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('rating')
                            ->label('Puanlama (Yıldız)')
                            ->options([
                                5 => '⭐⭐⭐⭐⭐ (5 - Mükemmel)',
                                4 => '⭐⭐⭐⭐ (4 - Çok İyi)',
                                3 => '⭐⭐⭐ (3 - Orta)',
                                2 => '⭐⭐ (2 - Geliştirilmeli)',
                                1 => '⭐ (1 - Yetersiz)',
                            ])
                            ->default(5)
                            ->required(),

                        Select::make('status')
                            ->label('Yayın Durumu')
                            ->options(ReviewStatus::class)
                            ->default(ReviewStatus::Yayinda)
                            ->required(),
                    ]),

                Section::make('Yorum & Deneyim Detayı')
                    ->description('Adayın şirket, çalışma ortamı veya mülakat süreci hakkındaki geri bildirimi.')
                    ->schema([
                        Textarea::make('comment')
                            ->label('Değerlendirme Yorumu')
                            ->rows(5)
                            ->maxLength(3000)
                            ->placeholder('Şirketin çalışma kültürü, iş ortamı veya mülakat süreci hakkında adayın görüşleri...')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
