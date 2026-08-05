<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                /*
                 * BENZERSİZLİK DOĞRULAMASI — 2026-08-05'te eklendi.
                 *
                 * `email` ve `username` veritabanında unique ama formda kural
                 * YOKTU. Doğrulama veriyi geçiriyor, INSERT veritabanı
                 * seviyesinde patlıyordu (SQLSTATE 23000). Kullanıcı "bu
                 * e-posta zaten kayıtlı" yerine çıplak bir sunucu hatası
                 * görüyor, doldurduğu form da kayboluyordu.
                 *
                 * Sahip ikinci yöneticiyi eklerken tam olarak buna çarptı.
                 *
                 * `ignoreRecord: true` şart: olmadan bir kullanıcıyı DÜZENLEYİP
                 * kaydetmek "bu e-posta zaten alınmış" derdi — kendi kaydını
                 * çakışma sanardı.
                 */
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    // Oluştururken zorunlu; düzenlerken boş bırakılırsa parola
                    // DEĞİŞMEZ (dehydrated=false → veri yazılmaz). Eskiden her
                    // düzenlemede zorunluydu ve admin farkında olmadan parolayı
                    // değiştiriyordu.
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->revealable(),
                // Boş bırakılabilir (DB'de nullable) ama doluysa benzersiz
                // olmalı — birden fazla NULL unique index'i bozmaz.
                TextInput::make('username')
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('avatar_path'),
                Textarea::make('bio')
                    ->columnSpanFull(),
                TextInput::make('country_code'),
                TextInput::make('city'),
                TextInput::make('preferred_currency')
                    ->required()
                    ->default('EUR'),
                Select::make('role')
                    ->options(UserRole::class)
                    ->default('uye')
                    ->required()
                    // Rolü yalnızca Admin değiştirebilir. Moderatör için alan
                    // kilitli görünür VE kaydederken hiç yazılmaz (dehydrated
                    // false) — aksi halde moderatör kendini Admin'e yükseltebilirdi.
                    ->disabled(fn (): bool => ! auth()->user()?->isAdmin())
                    ->dehydrated(fn (): bool => auth()->user()?->isAdmin() ?? false),
                Toggle::make('is_verified')
                    ->required(),
                Select::make('status')
                    ->options(UserStatus::class)
                    ->default('aktif')
                    ->required(),
                DateTimePicker::make('last_seen_at'),
            ]);
    }
}
