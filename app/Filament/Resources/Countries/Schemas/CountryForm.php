<?php

namespace App\Filament\Resources\Countries\Schemas;

use App\Services\Ai\SystemToolsAiAssistant;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('ISO Kodu (2 Harf)')
                    ->required()
                    ->maxLength(2)
                    ->placeholder('DE')
                    ->extraInputAttributes(['style' => 'text-transform: uppercase']),

                TextInput::make('name_tr')
                    ->label('Türkçe Adı')
                    ->required()
                    ->placeholder('Almanya')
                    ->hintAction(
                        Action::make('aiTamamla')
                            ->label('AI ile Bilgileri Doldur')
                            ->icon(Heroicon::OutlinedSparkles)
                            ->action(function (callable $get, callable $set, SystemToolsAiAssistant $assistant): void {
                                $query = (string) ($get('name_tr') ?: $get('code'));
                                if (blank($query)) {
                                    Notification::make()->title('Lütfen önce ülke adı veya kodu giriniz')->warning()->send();

                                    return;
                                }

                                $details = $assistant->suggestCountryDetails($query);
                                if (blank($get('code')) || strlen((string) $get('code')) < 2) {
                                    $set('code', $details['code']);
                                }
                                $set('name_tr', $details['name_tr']);
                                $set('emoji', $details['emoji']);
                                $set('default_currency', $details['default_currency']);

                                Notification::make()
                                    ->title("Ülke bilgileri tamamlandı: {$details['emoji']} {$details['name_tr']} ({$details['default_currency']})")
                                    ->success()
                                    ->send();
                            })
                    ),

                TextInput::make('emoji')
                    ->label('Bayrak Emojisi')
                    ->maxLength(8)
                    ->placeholder('🇩🇪'),

                TextInput::make('default_currency')
                    ->label('Varsayılan Para Birimi')
                    ->maxLength(3)
                    ->placeholder('EUR')
                    ->extraInputAttributes(['style' => 'text-transform: uppercase']),

                Toggle::make('is_active')
                    ->label('Aktif mi?')
                    ->default(true)
                    ->required(),

                TextInput::make('sort_order')
                    ->label('Sıralama')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
