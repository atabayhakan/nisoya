<?php

namespace App\Filament\Resources\OutreachTargets\Tables;

use App\Filament\Resources\BekleyenHamleler\BekleyenHamlelerResource;
use App\Models\BekleyenHamle;
use App\Models\OutreachTarget;
use App\Services\Ai\GrowthMarketingAiAssistant;
use App\Services\Growth\ClaimableListingCreator;
use App\Services\Growth\ErisimMesajiYazari;
use App\Services\Growth\WhatsAppDavetServisi;
use App\Support\Growth\GrowthCatalog;
use App\Support\QrKodu;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OutreachTargetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('İşletme')
                    ->description(fn (OutreachTarget $r): ?string => $r->city ? $r->city.' · '.$r->sector : $r->sector)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('country')
                    ->label('Ülke')
                    ->badge(),
                TextColumn::make('detection_band')
                    ->label('Sonuç')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'turkish' => 'success',
                        'ambiguous' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'turkish' => '✓ Türk',
                        'ambiguous' => '? Sınırda',
                        default => 'Türk değil',
                    }),
                TextColumn::make('detection_confidence')
                    ->label('Güven')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('marketing_status')
                    ->label('Gönderim')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'allowed' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'allowed' ? 'gönderilebilir' : 'engelli'),
                TextColumn::make('signals_text')
                    ->label('Sinyaller')
                    ->state(fn (OutreachTarget $r): string => implode('; ', $r->detection_signals ?? []))
                    ->wrap()
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('contact_email')
                    ->label('E-posta')
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('listing_id')
                    ->label('Vitrin')
                    ->badge()
                    ->color(fn (OutreachTarget $r): string => match (true) {
                        $r->listing === null => 'gray',
                        $r->listing->isClaimable() => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (OutreachTarget $r): string => match (true) {
                        $r->listing === null => 'Yok',
                        $r->listing->isClaimable() => 'Bekliyor',
                        default => 'Sahiplenildi',
                    })
                    ->url(fn (OutreachTarget $r): ?string => $r->listing ? route('listings.show', [$r->listing->id, $r->listing->slug]) : null)
                    ->openUrlInNewTab(),
                IconColumn::make('needs_review')
                    ->label('İnceleme')
                    ->boolean(),
                TextColumn::make('source')
                    ->label('Kaynak')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('detection_band')
                    ->label('Sonuç')
                    ->options([
                        'turkish' => '✓ Türk',
                        'ambiguous' => '? Sınırda',
                    ]),
                SelectFilter::make('marketing_status')
                    ->label('Gönderim')
                    ->options([
                        'allowed' => 'Gönderilebilir',
                        'region_blocked' => 'Engelli (AB/TR/RU)',
                    ]),
                SelectFilter::make('country')
                    ->label('Ülke')
                    ->options(array_combine(array_keys(GrowthCatalog::CITIES), array_keys(GrowthCatalog::CITIES))),
                TernaryFilter::make('needs_review')
                    ->label('İnceleme bekleyen'),
            ])
            ->defaultSort('detection_confidence', 'desc')
            // Uzun havuzda gezinmeyi kolaylaştır: sayfa boyutu seçenekleri +
            // sayfa değişince otomatik en üste kaydır (Filament sayfa numaralarını
            // tablonun ALTINDA gösterir — üstte arama + filtreler zaten var).
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->scrollToTopOnPageChange()
            ->recordActions([
                Action::make('google')
                    ->label('Google')
                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                    ->color('gray')
                    ->url(fn (OutreachTarget $r): string => 'https://www.google.com/search?q='.rawurlencode(trim($r->name.' '.$r->city.' '.$r->country)))
                    ->openUrlInNewTab(),
                Action::make('maps')
                    ->label('Maps')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->color('gray')
                    ->url(fn (OutreachTarget $r): string => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(trim($r->name.' '.$r->city)))
                    ->openUrlInNewTab(),
                Action::make('aiKulturelAnaliz')
                    ->label('AI Kültürel Analiz')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('primary')
                    ->modalHeading(fn (OutreachTarget $r): string => "AI Kültürel Analiz: {$r->name}")
                    ->modalSubmitActionLabel('Önerilen Sonucu Uygula')
                    ->modalContent(function (OutreachTarget $r) {
                        $assistant = app(GrowthMarketingAiAssistant::class);
                        $analiz = $assistant->classifyTargetCulture([
                            'name' => (string) $r->name,
                            'city' => (string) $r->city,
                            'country' => (string) $r->country,
                            'sector' => (string) $r->sector,
                            'signals' => (array) ($r->detection_signals ?? []),
                        ]);

                        return view('filament.outreach.ai-kulturel-analiz-modal', [
                            'aday' => $r,
                            'analiz' => $analiz,
                        ]);
                    })
                    ->action(function (OutreachTarget $r): void {
                        $assistant = app(GrowthMarketingAiAssistant::class);
                        $analiz = $assistant->classifyTargetCulture([
                            'name' => (string) $r->name,
                            'city' => (string) $r->city,
                            'country' => (string) $r->country,
                            'sector' => (string) $r->sector,
                            'signals' => (array) ($r->detection_signals ?? []),
                        ]);

                        if ($analiz['recommendation'] === 'onayla') {
                            $r->update([
                                'needs_review' => false,
                                'status' => 'onayli',
                                'detection_band' => 'turkish',
                                'detection_confidence' => $analiz['confidence'],
                            ]);
                            Notification::make()->title("{$r->name} Onaylandı ✓")->success()->send();
                        } elseif ($analiz['recommendation'] === 'reddet') {
                            $r->update([
                                'needs_review' => false,
                                'status' => 'reddedildi',
                                'detection_band' => 'not_turkish',
                            ]);
                            Notification::make()->title("{$r->name} Reddedildi")->warning()->send();
                        } else {
                            Notification::make()->title('İnceleme Gerekli: Durum Değiştirilmedi')->info()->send();
                        }
                    }),
                Action::make('onayla')
                    ->label('Onayla')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (OutreachTarget $r): bool => $r->needs_review)
                    ->action(fn (OutreachTarget $r) => $r->update(['needs_review' => false, 'status' => 'onayli'])),
                Action::make('reddet')
                    ->label('Reddet')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (OutreachTarget $r): bool => $r->needs_review)
                    ->action(fn (OutreachTarget $r) => $r->update(['needs_review' => false, 'status' => 'reddedildi'])),
                /*
                 * MESAJ TASLAĞI — Kâhya yazar, SAHİP gönderir.
                 *
                 * Aksiyon yalnız GÖNDERİME AÇIK adaylarda görünür: hukuki
                 * kapı (Türk + izinli bölge) burada da geçerli. Kapalı bir
                 * aday için taslak göstermek, o kapının varlık sebebini boşa
                 * çıkarırdı — sahip taslağı görür ve gönderir.
                 *
                 * Modal salt-okunur bir metin kutusu: gönderme düğmesi YOK,
                 * bilerek. Sahip metni okur, kopyalar, kendi posta
                 * programından gönderir. (AWS üretim erişimi reddedildikten
                 * sonra soğuk e-posta otomasyonu zaten bırakılmıştı.)
                 */
                Action::make('mesaj-taslagi')
                    ->label('Mesaj taslağı')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->color('primary')
                    ->modalHeading('Tanışma postası taslağı')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat')
                    ->visible(fn (OutreachTarget $r): bool => app(ErisimMesajiYazari::class)->uygunMu($r))
                    ->modalContent(function (OutreachTarget $r) {
                        $taslak = app(ErisimMesajiYazari::class)->taslak($r);

                        return view('filament.outreach.mesaj-taslagi', [
                            'aday' => $r,
                            'taslak' => $taslak,
                        ]);
                    }),
                Action::make('vitrin-olustur')
                    ->label('Vitrin Hazırla')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('warning')
                    ->visible(fn (OutreachTarget $r): bool => $r->listing_id === null)
                    ->requiresConfirmation()
                    ->modalHeading('Sahiplenilebilir Vitrin Oluştur')
                    ->modalDescription(fn (OutreachTarget $r): string => "{$r->name} ({$r->city}) için otomatik sahiplenilebilir vitrin ve davet bağlantısı oluşturulacak. Devam edilsin mi?")
                    ->action(function (OutreachTarget $r) {
                        $result = app(ClaimableListingCreator::class)->createFromTarget($r);
                        $r->refresh();

                        Notification::make()
                            ->title('Vitrin Hazırlandı!')
                            ->body("{$r->name} için vitrin oluşturuldu. Sahiplenme bağlantısı hazır.")
                            ->actions([
                                Action::make('goruntule')
                                    ->label('Sahiplenme Ekranı')
                                    ->url($result['claim_url'])
                                    ->openUrlInNewTab(),
                            ])
                            ->success()
                            ->send();
                    }),
                Action::make('hamle-olustur')
                    ->label('Hamle Kartı Aç')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->visible(fn (OutreachTarget $r): bool => filled($r->contact_email) && app(ErisimMesajiYazari::class)->uygunMu($r))
                    ->requiresConfirmation()
                    ->modalHeading('Kâhya Onay Kuyruğuna Ekle')
                    ->modalDescription(fn (OutreachTarget $r): string => "Bu işletme için hazırlanan davet mektubu Kâhya'nın onay kuyruğuna (Bekleyen Hamleler) eklenecek. Panelden onaylayarak gönderebilirsiniz.")
                    ->action(function (OutreachTarget $r) {
                        $taslak = app(ErisimMesajiYazari::class)->taslak($r);

                        $hamle = BekleyenHamle::create([
                            'listing_id' => $r->listing_id,
                            'baslik' => $taslak['konu'],
                            'gerekce' => "{$r->name} ({$r->city}) işletmesine vitrin sahiplenme daveti",
                            'icerik' => $taslak['mesaj'],
                            'tur' => 'eposta',
                            'alici_eposta' => mb_strtolower(trim((string) $r->contact_email)),
                        ]);

                        Notification::make()
                            ->title('Hamle Kartı Oluşturuldu (#'.$hamle->id.')')
                            ->body('Davet mektubu Kâhya Bekleyen Hamleler onay kuyruğuna eklendi.')
                            ->actions([
                                Action::make('incele')
                                    ->label('Hamlelere Git')
                                    ->url(BekleyenHamlelerResource::getUrl('index')),
                            ])
                            ->success()
                            ->send();
                    }),
                Action::make('whatsapp-davet')
                    ->label('WhatsApp Davet')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->color('success')
                    ->visible(fn (OutreachTarget $r): bool => filled($r->listing?->claim_phone) || filled($r->detection_signals['phone'] ?? null))
                    ->url(fn (OutreachTarget $r): string => app(WhatsAppDavetServisi::class)->adayIcinUrl($r))
                    ->openUrlInNewTab(),
                Action::make('aiKisiselDavet')
                    ->label('AI Kişisel Davet')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('primary')
                    ->modalHeading(fn (OutreachTarget $r): string => "AI Kişiselleştirilmiş Davet: {$r->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat')
                    ->modalContent(function (OutreachTarget $r) {
                        $assistant = app(GrowthMarketingAiAssistant::class);
                        $claimUrl = $r->listing && $r->listing->isClaimable()
                            ? url('/sahiplen/'.$r->listing->claim_token)
                            : url('/sahiplen');

                        $davet = $assistant->draftPersonalizedOutreach([
                            'name' => (string) $r->name,
                            'city' => (string) $r->city,
                            'country' => (string) $r->country,
                            'sector' => (string) $r->sector,
                        ], $claimUrl);

                        $phone = $r->listing?->claim_phone ?: ($r->detection_signals['phone'] ?? null);
                        $cleanPhone = $phone ? preg_replace('/[^\d+]/', '', (string) $phone) : '';
                        $waLink = $cleanPhone
                            ? 'https://wa.me/'.ltrim((string) $cleanPhone, '+').'?text='.rawurlencode($davet['whatsapp_message'])
                            : 'https://wa.me/?text='.rawurlencode($davet['whatsapp_message']);

                        return view('filament.outreach.ai-davet-modal', [
                            'aday' => $r,
                            'davet' => $davet,
                            'waLink' => $waLink,
                            'claimUrl' => $claimUrl,
                        ]);
                    }),
                Action::make('qr-ve-kart')
                    ->label('QR & Kart')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->color('gray')
                    ->visible(fn (OutreachTarget $r): bool => $r->listing !== null)
                    ->modalHeading('İşletme QR Kodu & Tanıtım Kiti')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat')
                    ->modalContent(function (OutreachTarget $r) {
                        $listing = $r->listing;
                        $listingUrl = $listing ? route('listings.show', [$listing->id, $listing->slug]) : url('/ilanlar');
                        $claimUrl = ($listing && $listing->isClaimable()) ? url('/sahiplen/'.$listing->claim_token) : null;
                        $qrSvg = QrKodu::svg($listingUrl, 240);

                        return view('filament.outreach.qr-ve-kart', [
                            'aday' => $r,
                            'listing' => $listing,
                            'listingUrl' => $listingUrl,
                            'claimUrl' => $claimUrl,
                            'qrSvg' => $qrSvg,
                        ]);
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
