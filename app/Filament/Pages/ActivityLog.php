<?php

namespace App\Filament\Pages;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;
use BackedEnum;

/**
 * Admin işlem geçmişi — Spatie ActivityLog görüntüleyici.
 * Kullanıcı ban, ilan onay/red, öne çıkarma onayı vb. tüm kritik aksiyonlar.
 */
class ActivityLog extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'İşlem Geçmişi';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.activity-log';

    public function getTitle(): string
    {
        return 'İşlem Geçmişi';
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Activity::query()->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('created_at')
                ->label('Tarih')
                ->dateTime('d.m.Y H:i')
                ->sortable(),

            TextColumn::make('log_name')
                ->label('Kategori')
                ->badge()
                ->color(fn (?string $state): string => match ($state) {
                    'user' => 'info',
                    'listing' => 'success',
                    default => 'gray',
                })
                ->placeholder('—'),

            TextColumn::make('description')
                ->label('İşlem')
                ->searchable(),

            TextColumn::make('causer.name')
                ->label('Yapan')
                ->default('Sistem')
                ->searchable(),

            TextColumn::make('subject_type')
                ->label('Hedef Tür')
                ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—')
                ->placeholder('—'),

            TextColumn::make('subject_id')
                ->label('Hedef ID')
                ->placeholder('—'),

            TextColumn::make('changes')
                ->label('Değişiklikler')
                ->getStateUsing(function (Activity $record) {
                    $properties = $record->properties;
                    if (! isset($properties['attributes']) || ! isset($properties['old'])) {
                        return '—';
                    }

                    $changes = [];
                    foreach ($properties['attributes'] as $key => $newValue) {
                        $oldValue = $properties['old'][$key] ?? null;
                        if ($oldValue !== $newValue) {
                            $changes[] = $key.': '.($oldValue ?? '—').' → '.($newValue ?? '—');
                        }
                    }

                    return empty($changes) ? '—' : implode("\n", $changes);
                })
                ->wrap()
                ->placeholder('—'),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('log_name')
                ->label('Kategori')
                ->options([
                    'user' => 'Kullanıcı',
                    'listing' => 'İlan',
                ]),
            Filter::make('created_at')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('created_from')->label('Başlangıç'),
                    \Filament\Forms\Components\DatePicker::make('created_until')->label('Bitiş'),
                ])
                ->query(function ($query, array $data) {
                    return $query
                        ->when($data['created_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['created_until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                }),
        ];
    }

    protected function getTableDefaultSort(): string
    {
        return 'created_at';
    }

    protected function getTableDefaultSortDirection(): ?string
    {
        return 'desc';
    }
}