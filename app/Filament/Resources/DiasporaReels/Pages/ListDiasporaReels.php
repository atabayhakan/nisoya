<?php

namespace App\Filament\Resources\DiasporaReels\Pages;

use App\Filament\Resources\DiasporaReels\DiasporaReelResource;
use App\Models\Country;
use App\Models\DiasporaReel;
use App\Services\Ai\CmsAiAssistant;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListDiasporaReels extends ListRecords
{
    protected static string $resource = DiasporaReelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiReelsTaslagi')
                ->label('AI ile Hızlı Reel Ekle (Claude)')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Claude ile Diaspora Reels Hikayesi Oluştur')
                ->modalDescription('Diasporadaki bir etkinlik, buluşma veya lezzet konusunu yazın. Claude sizin için başlık, samimi hikaye notu ve şehir bilgilerini hazırlasın.')
                ->form([
                    TextInput::make('odak')
                        ->label('Konu / Odak Noktası')
                        ->placeholder('Örn: Köln\'de Türk gençlik spor buluşması veya Bişkek Türk börekçisi')
                        ->required(),
                    TextInput::make('instagram_url')
                        ->label('Instagram Reel / Gönderi Linki')
                        ->placeholder('https://www.instagram.com/reel/C8xABC12345/')
                        ->required(),
                    Select::make('country_code')
                        ->label('Ülke (Opsiyonel)')
                        ->options(function () {
                            return Country::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn (Country $c) => [$c->code => ($c->emoji ? $c->emoji.' ' : '').$c->name_tr]);
                        })
                        ->searchable()
                        ->nullable(),
                    TextInput::make('city')
                        ->label('Şehir (Opsiyonel)')
                        ->placeholder('Köln, Bişkek, Berlin...'),
                ])
                ->action(function (array $data, CmsAiAssistant $assistant): void {
                    $story = $assistant->generateDiasporaStory(
                        (string) $data['odak'],
                        isset($data['country_code']) ? (string) $data['country_code'] : null,
                        isset($data['city']) ? (string) $data['city'] : null
                    );

                    $countryCode = filled($data['country_code'] ?? null)
                        ? (string) $data['country_code']
                        : ($story['country_code'] ?? 'DE');

                    $city = filled($data['city'] ?? null)
                        ? (string) $data['city']
                        : ($story['suggested_city'] ?? null);

                    DiasporaReel::create([
                        'title' => $story['title'] ?? (string) $data['odak'],
                        'caption' => $story['caption'] ?? null,
                        'instagram_url' => (string) $data['instagram_url'],
                        'country_code' => $countryCode,
                        'city' => $city,
                        'instagram_username' => $story['suggested_username'] ?? null,
                        'is_active' => true,
                    ]);

                    Notification::make()
                        ->title('Diaspora Reel hikayesi oluşturuldu')
                        ->body('Claude AI başlık ve açıklamayı başarıyla hazırladı ve vitrine ekledi.')
                        ->success()
                        ->send();
                }),
            CreateAction::make()
                ->label('Manuel Reel Ekle'),
        ];
    }
}
