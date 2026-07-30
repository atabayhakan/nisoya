<?php

namespace App\Filament\Resources\KahyaHafiza\Schemas;

use App\Enums\HafizaTuru;
use App\Models\KahyaHafizasi;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class KahyaHafizaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tur')
                    ->label('Tür')
                    ->options(collect(HafizaTuru::cases())->mapWithKeys(
                        fn (HafizaTuru $t): array => [$t->value => $t->etiket()],
                    ))
                    ->default(HafizaTuru::Not->value)
                    ->required()
                    ->helperText('Kural ve Gerçek her sohbete girer; Ders ve Not yer kalırsa girer.'),
                Textarea::make('metin')
                    ->label('Metin')
                    ->required()
                    ->minLength(10)
                    ->maxLength(500)
                    ->rows(3)
                    ->helperText('Kısa ve kendi başına anlaşılır yaz — Kâhya bunu her sohbetin başında okuyacak.'),
                Toggle::make('aktif')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Kapalıysa kayıt durur ama Kâhya\'nın yönergesine girmez.'),
                // Kaynak formdan seçtirilmez: panelden ekleneni de "sahip" sayarız;
                // "kahya-cikarimi" yalnız F5 ders-cikar koşusunun yazacağı değer.
                Hidden::make('kaynak')
                    ->default(KahyaHafizasi::KAYNAK_SAHIP),
            ]);
    }
}
