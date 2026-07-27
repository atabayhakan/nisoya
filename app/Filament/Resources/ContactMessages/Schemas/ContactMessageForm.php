<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use App\Enums\ContactCategory;
use App\Enums\ContactMessageStatus;
use App\Enums\ContactPriority;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gönderen')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Ad Soyad')->disabled(),
                        TextInput::make('email')->label('E-posta')->disabled(),
                        TextInput::make('phone')->label('Telefon')->disabled(),
                        Select::make('category')
                            ->label('Konu')
                            ->options(ContactCategory::class)
                            ->disabled(),
                        Textarea::make('message')
                            ->label('Mesaj')
                            ->disabled()
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),
                Section::make('Yönetim')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Durum')
                            ->options(ContactMessageStatus::class)
                            ->required(),
                        Select::make('priority')
                            ->label('Öncelik')
                            ->options(ContactPriority::class)
                            ->default(ContactPriority::Normal->value)
                            ->required(),
                        Select::make('assigned_to')
                            ->label('Üstlenen')
                            ->relationship('assignee', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Üstlenilmedi'),
                        Placeholder::make('ilk_yanit')
                            ->label('İlk yanıt süresi')
                            ->content(fn (?ContactMessage $record) => $record?->ilkYanitSuresi() ?? 'Henüz yanıtlanmadı'),
                        Textarea::make('admin_note')
                            ->label('İç not (yalnızca yönetim görür — misafire GİTMEZ)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                // Gönderilen yanıtların geçmişi. İç nottan ayrı: buradakiler
                // misafire gerçekten e-posta olarak gitti.
                Section::make('Gönderilen yanıtlar')
                    ->visible(fn (?ContactMessage $record) => $record && $record->replies()->exists())
                    ->schema([
                        Placeholder::make('yanit_gecmisi')
                            ->hiddenLabel()
                            ->content(fn (ContactMessage $record) => new HtmlString(
                                $record->replies
                                    ->map(function (ContactMessageReply $yanit) {
                                        $kim = e($yanit->user->name ?? 'Silinmiş kullanıcı');
                                        $ne_zaman = e($yanit->created_at->format('d.m.Y H:i'));
                                        $govde = nl2br(e($yanit->body));
                                        $durum = $yanit->basarisizMi()
                                            ? '<span style="color:#dc2626;font-weight:600">GÖNDERİLEMEDİ: '.e((string) $yanit->error).'</span>'
                                            : '<span style="color:#059669">Gönderildi</span>';

                                        return "<div style=\"margin-bottom:12px;padding:10px;border-left:3px solid #d4d4d8\">
                                            <div style=\"font-size:12px;color:#71717a\">{$kim} · {$ne_zaman} · {$durum}</div>
                                            <div style=\"margin-top:6px\">{$govde}</div>
                                        </div>";
                                    })
                                    ->implode('')
                            )),
                    ]),
            ]);
    }
}
