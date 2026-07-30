<?php

namespace App\Filament\Resources\KahyaGorevleri\Schemas;

use App\Models\KahyaGorevi;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KahyaGorevForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('baslik')
                    ->label('Başlık')
                    ->required()
                    ->minLength(5)
                    ->maxLength(150),
                Textarea::make('hedef')
                    ->label('Hedef')
                    ->required()
                    ->minLength(10)
                    ->maxLength(500)
                    ->rows(2)
                    ->helperText('Bitince neyin değişmiş olacağı — ölçülebilir tek cümle.'),
                Select::make('durum')
                    ->label('Durum')
                    ->options([
                        KahyaGorevi::DURUM_ACIK => 'Açık',
                        KahyaGorevi::DURUM_TAMAMLANDI => 'Tamamlandı',
                        KahyaGorevi::DURUM_IPTAL => 'İptal',
                    ])
                    ->default(KahyaGorevi::DURUM_ACIK)
                    ->required(),
                Repeater::make('adimlar')
                    ->label('Adım planı')
                    ->schema([
                        TextInput::make('metin')
                            ->label('Adım')
                            ->required()
                            ->maxLength(300),
                        Select::make('durum')
                            ->label('Durum')
                            ->options(['bekliyor' => 'Bekliyor', 'yapildi' => 'Yapıldı', 'atlandi' => 'Atlandı'])
                            ->default('bekliyor')
                            ->required(),
                    ])
                    ->columns(2)
                    ->reorderable()
                    ->defaultItems(0)
                    ->addActionLabel('Adım ekle'),
            ]);
    }
}
