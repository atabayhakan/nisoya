<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\CategoryType;
use App\Services\Ai\MarketplaceAiAssistant;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Ad')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->label('Kısa ad (URL)')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('parent_id')
                    ->label('Üst kategori')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('— Yok (ana kategori)'),
                TextInput::make('icon')
                    ->label('İkon (emoji)')
                    ->maxLength(8)
                    ->placeholder('📚')
                    ->hintAction(
                        Action::make('aiEmojiOner')
                            ->label('AI Emoji')
                            ->icon(Heroicon::OutlinedSparkles)
                            ->action(function (callable $get, callable $set, MarketplaceAiAssistant $assistant): void {
                                $name = (string) $get('name');
                                if (blank($name)) {
                                    Notification::make()->title('Önce kategori adı giriniz')->warning()->send();

                                    return;
                                }
                                $emoji = $assistant->suggestCategoryEmoji($name);
                                $set('icon', $emoji);
                                Notification::make()->title("Emoji belirlendi: {$emoji}")->success()->send();
                            })
                    ),
                Select::make('type')
                    ->label('Tür')
                    ->options(CategoryType::class)
                    ->default('hizmet')
                    ->required(),
                TextInput::make('sort_order')
                    ->label('Sıra')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
