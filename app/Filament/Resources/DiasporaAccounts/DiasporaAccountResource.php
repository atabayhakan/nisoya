<?php

namespace App\Filament\Resources\DiasporaAccounts;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\DiasporaAccounts\Pages\CreateDiasporaAccount;
use App\Filament\Resources\DiasporaAccounts\Pages\EditDiasporaAccount;
use App\Filament\Resources\DiasporaAccounts\Pages\ListDiasporaAccounts;
use App\Models\Country;
use App\Models\DiasporaAccount;
use App\Services\Diaspora\DiasporaSyncEngine;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * İzlenen Diaspora Hesapları & İçerik Havuzu Yönetimi.
 *
 * Almanya, Kırgızistan, Hollanda vb. ülkelerdeki Türk topluluğu
 * Instagram hesaplarını takip eder ve yeni reels gönderilerini yakalar.
 */
class DiasporaAccountResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = DiasporaAccount::class;

    protected static ?string $slug = 'diaspora-hesaplari';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAtSymbol;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik & Tasarım (CMS)';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return 'İzlenen Diaspora Hesapları';
    }

    public static function getModelLabel(): string
    {
        return 'İzlenen Diaspora Hesabı';
    }

    public static function getPluralModelLabel(): string
    {
        return 'İzlenen Diaspora Hesapları';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('username')
                ->label('Instagram Kullanıcı Adı')
                ->required()
                ->maxLength(100)
                ->placeholder('@berlinturkleri')
                ->prefixIcon('heroicon-m-at-symbol')
                ->helperText('Örn: @berlinturkleri veya @biskek_turkleri'),

            TextInput::make('title')
                ->label('Topluluk / Sayfa Adı')
                ->maxLength(150)
                ->placeholder('Berlin Türkleri Topluluğu'),

            Select::make('country_code')
                ->label('Bağlı Olduğu Ülke')
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
                ->label('Şehir')
                ->placeholder('Berlin, Bişkek, Frankfurt, Amsterdam...')
                ->maxLength(100),

            Textarea::make('description')
                ->label('Açıklama / Topluluk Notu')
                ->rows(2)
                ->placeholder('Bu hesap diasporadaki etkinlikleri ve esnafları paylaşmaktadır.')
                ->columnSpanFull(),

            Toggle::make('is_verified')
                ->label('Doğrulanmış Topluluk Hesabı')
                ->helperText('Resmi dernek, tanınmış topluluk veya güvenilir yerel sayfa.')
                ->default(false),

            Toggle::make('autopilot')
                ->label('Otopilot Modu')
                ->helperText('Açık ise; bu hesaptan çekilen güvenli içerikler admin onayına düşmeden otomatik yayına alınır.')
                ->default(false),

            Toggle::make('is_active')
                ->label('Aktif Takipte')
                ->helperText('Kapalı ise bu hesaptan yeni içerik taranmaz.')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label('Instagram')
                    ->weight('bold')
                    ->icon('heroicon-m-camera')
                    ->iconColor('danger')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Topluluk / Sayfa Adı')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('country.name_tr')
                    ->label('Ülke')
                    ->formatStateUsing(fn (DiasporaAccount $record): string => ($record->country ? ($record->country->emoji.' ') : '').($record->country ? $record->country->name_tr : '—'))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('city')
                    ->label('Şehir')
                    ->icon('heroicon-m-map-pin')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('reels_count')
                    ->label('Çekilen Reel')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                ToggleColumn::make('autopilot')
                    ->label('Otopilot')
                    ->alignCenter(),

                IconColumn::make('is_verified')
                    ->label('Doğrulanmış')
                    ->boolean()
                    ->alignCenter(),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->alignCenter(),

                TextColumn::make('last_synced_at')
                    ->label('Son Tarama')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Henüz taranmadı')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('country_code')
                    ->label('Ülke')
                    ->options(fn (): array => Country::query()->where('is_active', true)->orderBy('sort_order')->get()->mapWithKeys(fn (Country $c) => [$c->code => ($c->emoji ? $c->emoji.' ' : '').$c->name_tr])->toArray()),

                TernaryFilter::make('autopilot')
                    ->label('Otopilot')
                    ->trueLabel('Otopilot Açık')
                    ->falseLabel('Manuel Onaylı'),

                TernaryFilter::make('is_active')
                    ->label('Takip Durumu')
                    ->trueLabel('Aktif İzlenenler')
                    ->falseLabel('Pasifler'),
            ])
            ->actions([
                Action::make('tara')
                    ->label('Şimdi Tara')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('primary')
                    ->action(function (DiasporaAccount $record, DiasporaSyncEngine $syncEngine): void {
                        $res = $syncEngine->syncAccount($record);

                        Notification::make()
                            ->title("{$record->username} Tarandı")
                            ->body("{$res['created']} yeni video aktarıldı ({$res['autopilot_published']} otopilot yayında, kalanı onay bekliyor). {$res['skipped']} içerik zaten mevcuttu.")
                            ->success()
                            ->send();
                    }),

                Action::make('profil')
                    ->label('Instagram')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (DiasporaAccount $record): string => $record->instagram_url)
                    ->openUrlInNewTab(),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Henüz Takip Edilen Diaspora Hesabı Yok')
            ->emptyStateDescription('Almanya, Kırgızistan, Hollanda vb. bölgelerdeki Türk topluluğu hesaplarını buraya ekleyerek Reels videolarını otomatik olarak toplayabilirsiniz.')
            ->emptyStateIcon(Heroicon::OutlinedAtSymbol);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiasporaAccounts::route('/'),
            'create' => CreateDiasporaAccount::route('/create'),
            'edit' => EditDiasporaAccount::route('/{record}/edit'),
        ];
    }
}
