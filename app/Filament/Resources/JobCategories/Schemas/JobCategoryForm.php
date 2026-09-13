<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class JobCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sektör & Kategori Kimliği')
                    ->description('İş ilanlarının sınıflandırıldığı sektör başlığı ve simgesi.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Kategori / Sektör Adı')
                            ->placeholder('örn. Yazılım & Teknoloji')
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Kısa Ad (URL Slug)')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->prefix('nisoya.com/isler?kategori='),

                        TextInput::make('icon')
                            ->label('Heroicon Simge Adı')
                            ->placeholder('örn. code-bracket, brief-case, truck')
                            ->maxLength(50)
                            ->datalist([
                                'code-bracket',
                                'cake',
                                'wrench',
                                'heart',
                                'academic-cap',
                                'truck',
                                'shopping-bag',
                                'sparkles',
                                'scissors',
                                'briefcase',
                                'megaphone',
                                'calculator',
                                'language',
                                'cog-6-tooth',
                                'building-office',
                                'cube',
                                'phone',
                                'camera',
                            ])
                            ->helperText('Heroicon outline simge adı (örn: brief-case, truck, code-bracket).'),

                        TextInput::make('sort_order')
                            ->label('Görüntülenme Sırası')
                            ->numeric()
                            ->default(0)
                            ->helperText('Küçük sayılar menü ve filtrelerde daha üstte listelenir.'),
                    ]),

                Section::make('Yayın & Görünürlük')
                    ->description('Kategorinin sitede ve arama filtrelerinde yayında olma durumu.')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Aktif Kategori (Sitede Yayında)')
                            ->default(true)
                            ->helperText('Açık olduğunda adaylar bu kategorideki ilanları listeleyebilir ve filtreleyebilir.'),
                    ]),
            ]);
    }
}
