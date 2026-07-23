<?php

namespace App\Filament\Resources\OutreachTargets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OutreachTargetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('İşletme')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Ad')->disabled(),
                        TextInput::make('category')->label('Kategori')->disabled(),
                        TextInput::make('owner_name')->label('Sahip/kişi')->disabled(),
                        TextInput::make('country')->label('Ülke')->disabled(),
                        TextInput::make('city')->label('Şehir')->disabled(),
                        TextInput::make('website')->label('Web sitesi')->disabled(),
                    ]),
                Section::make('Tespit')
                    ->columns(2)
                    ->schema([
                        TextInput::make('detection_band')->label('Sonuç')->disabled(),
                        TextInput::make('detection_confidence')->label('Güven (%)')->disabled(),
                        TextInput::make('detection_method')->label('Yöntem')->disabled(),
                        TextInput::make('marketing_status')->label('Gönderim durumu')->disabled(),
                        Textarea::make('detection_reasoning')
                            ->label('Gerekçe')
                            ->disabled()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Karar')
                    ->schema([
                        Select::make('status')
                            ->label('Durum')
                            ->options([
                                'kesif' => 'Keşif (yeni)',
                                'onayli' => 'Onaylı',
                                'reddedildi' => 'Reddedildi',
                            ])
                            ->required(),
                        TextInput::make('contact_email')
                            ->label('İletişim e-postası (zenginleştirme)')
                            ->email()
                            ->placeholder('info@... (sonraki fazda otomatik doldurulur)'),
                    ]),
            ]);
    }
}
