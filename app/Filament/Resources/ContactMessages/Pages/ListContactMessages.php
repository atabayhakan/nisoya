<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Enums\ContactMessageStatus;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    /**
     * Durum sekmeleri (destek sistemi, 2026-07-27).
     *
     * "Açık" varsayılan sekmedir: sahibin paneli açtığında ilk gördüğü şey
     * hâlâ ilgilenilmesi gerekenler olsun — kapanmış biletler araya girmesin.
     *
     * @return array<string,Tab>
     */
    public function getTabs(): array
    {
        return [
            'acik' => Tab::make('Açık')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', [
                    ContactMessageStatus::Yeni->value,
                    ContactMessageStatus::Okundu->value,
                ]))
                ->badge(ContactMessage::query()->acik()->count() ?: null)
                ->badgeColor('danger'),

            'bana_atanan' => Tab::make('Bende')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('assigned_to', auth()->id()))
                ->badge(ContactMessage::query()->where('assigned_to', auth()->id())->acik()->count() ?: null)
                ->badgeColor('warning'),

            'yanitlandi' => Tab::make('Yanıtlandı')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', ContactMessageStatus::Yanitlandi->value)),

            'kapandi' => Tab::make('Kapandı')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', ContactMessageStatus::Kapandi->value)),

            'hepsi' => Tab::make('Hepsi'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'acik';
    }
}
