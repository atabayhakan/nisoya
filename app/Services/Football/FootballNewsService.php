<?php

namespace App\Services\Football;

use App\Contracts\AiProvider;
use App\Models\FootballMatch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FootballNewsService
{
    public function __construct(private readonly AiProvider $ai) {}

    /**
     * Doğrulanmış bir maç için editoryal spor haberi başlığı, özeti ve gövdesi üretir.
     *
     * @return array{title: string, summary: string, body: string}
     */
    public function generateMatchNews(FootballMatch $match): array
    {
        // 1. AI açık ve yapılandırılmışsa AI ile üretmeyi dene
        if ($this->ai->isConfigured() && config('ai.features.quick_listing', true)) {
            try {
                $aiResult = $this->generateWithAi($match);
                if ($aiResult) {
                    return $aiResult;
                }
            } catch (\Throwable $e) {
                Log::warning('Futbol maç haberi AI üretimi başarısız, şablona dönülüyor', [
                    'match_id' => $match->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 2. Şablon tabanlı uydurmasız güvenli üretim
        return $this->generateWithTemplate($match);
    }

    /**
     * AI ile uydurmasız spor haberi üretir.
     *
     * @return array{title: string, summary: string, body: string}|null
     */
    protected function generateWithAi(FootballMatch $match): ?array
    {
        $homeName = $match->homeTeam?->name ?: 'Ev Sahibi';
        $awayName = $match->awayTeam?->name ?: 'Deplasman';
        $homeScore = (int) $match->home_score;
        $awayScore = (int) $match->away_score;
        $venue = $match->venueDisplay();
        $city = $match->city;
        $date = $match->match_date->translatedFormat('d F Y');
        $mvp = $match->mvpPlayer?->name;

        $prompt = implode("\n", [
            'Aşağıdaki halı saha maç verilerini kullanarak heyecanlı, dinamik ama KESİNLİKLE GERÇEK DIŞI BİLGİ İÇERMEYEN bir Türkçe spor haberi yaz.',
            '',
            'KURALLAR (ÇOK KATI):',
            '- ASLA veri tabanında olmayan seyirci sayısı, hakem kararı, sakatlık, kavga, hava durumu gibi uydurma olaylar YAZMA.',
            '- Yalnızca verilen skor, takımlar, şehir, saha adı, tarih ve varsa maçın adamı üzerinden metin kurgula.',
            '- Başlık: Çarpıcı, spor gazetesi manşeti tarzında (Örn: "BERLİN\'DE GOL ŞÖLENİ: Berlin Türkler FC 7-4 Kreuzberg FC")',
            '- Özet: 1-2 cümlelik net özet.',
            '- Gövde: 2-3 paragraflık keyifli spor haberi metni.',
            '',
            "MAÇ BİLGİLERİ:",
            "- Şehir: {$city}",
            "- Saha: {$venue}",
            "- Tarih: {$date}",
            "- Ev Sahibi: {$homeName} ({$homeScore} gol)",
            "- Rakip / Deplasman: {$awayName} ({$awayScore} gol)",
            "- Maçın Adamı: ".($mvp ?: 'Belirtilmedi'),
        ]);

        $schema = [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'summary' => ['type' => 'string'],
                'body' => ['type' => 'string'],
            ],
            'required' => ['title', 'summary', 'body'],
        ];

        $result = $this->ai->analyzeText($prompt, $schema);

        if (is_array($result) && ! empty($result['title']) && ! empty($result['body'])) {
            return [
                'title' => (string) $result['title'],
                'summary' => (string) ($result['summary'] ?? Str::limit((string) $result['body'], 150)),
                'body' => (string) $result['body'],
            ];
        }

        return null;
    }

    /**
     * Şablon tabanlı güvenilir haber üretimi (Fallback).
     *
     * @return array{title: string, summary: string, body: string}
     */
    public function generateWithTemplate(FootballMatch $match): array
    {
        $homeName = $match->homeTeam?->name ?: 'Ev Sahibi';
        $awayName = $match->awayTeam?->name ?: 'Deplasman';
        $homeScore = (int) $match->home_score;
        $awayScore = (int) $match->away_score;
        $venue = $match->venueDisplay();
        $city = $match->city;
        $date = $match->match_date->translatedFormat('d F Y');
        $mvp = $match->mvpPlayer?->name;

        $totalGoals = $homeScore + $awayScore;

        if ($homeScore > $awayScore) {
            $diff = $homeScore - $awayScore;
            if ($totalGoals >= 8) {
                $title = "⚽ {$city}'de Gol Yağmuru! {$homeName} {$homeScore} - {$awayScore} {$awayName}";
            } elseif ($diff >= 3) {
                $title = "🔥 {$homeName} Rahat Kazandı: {$homeScore} - {$awayScore}";
            } else {
                $title = "🏆 Nefes Kesen Mücadele: {$homeName} {$homeScore} - {$awayScore} {$awayName}";
            }
            $summary = "{$city}'de oynanan halı saha karşılaşmasında {$homeName}, {$awayName} karşısında {$homeScore}-{$awayScore} galip gelerek haftayı 3 puanla tamamladı.";
            $body = "{$city} halı saha topluluğunun bu haftaki heyecan dolu karşılaşmasında {$homeName} ile {$awayName}, {$venue} tesislerinde karşı karşıya geldi.\n\n"
                  . "Karşılıklı ataklarla geçen mücadelede {$homeName}, sahadan {$homeScore}-{$awayScore} galibiyetle ayrılarak şehir ligindeki puanını artırdı."
                  . ($mvp ? "\n\nMaçın adamı olarak gösterilen {$mvp}, performansıyla maça damgasını vurdu." : '')
                  . "\n\nMaçın tüm ayrıntıları ve puan tablosu Nisoya Halı Saha Liginde güncellendi.";
        } elseif ($awayScore > $homeScore) {
            $diff = $awayScore - $homeScore;
            if ($totalGoals >= 8) {
                $title = "⚽ {$city}'de Deplasman Fırtınası! {$awayName} {$awayScore} - {$homeScore} {$homeName}";
            } else {
                $title = "🔥 {$awayName} Deplasmanda Güldü: {$awayScore} - {$homeScore}";
            }
            $summary = "{$city}'de {$venue} sahasında oynanan karşılaşmada {$awayName}, {$homeName} takımını {$awayScore}-{$homeScore} mağlup etti.";
            $body = "{$city}'de {$date} tarihinde oynanan maçta {$awayName}, deplasmanda {$homeName} karşısında üstün bir oyun sergileyerek sahadan {$awayScore}-{$homeScore} galibiyetle ayrıldı.\n\n"
                  . "Tempolu geçen müsabakada her iki takım da büyük bir mücadele örneği sergiledi."
                  . ($mvp ? "\n\nKarşılaşmanın en değerli oyuncusu seçilen {$mvp}, galibiyette kritik rol oynadı." : '')
                  . "\n\nDoğrulanan maç sonucuyla birlikte şehir lig sıralaması güncellendi.";
        } else {
            $title = "🤝 {$city}'de Puanlar Paylaşıldı: {$homeName} {$homeScore} - {$awayScore} {$awayName}";
            $summary = "{$homeName} ile {$awayName} arasındaki çekişmeli randevu {$homeScore}-{$awayScore} eşitlikle sonuçlandı.";
            $body = "{$city}'de {$venue} zemininde oynanan halı saha maçında {$homeName} ve {$awayName} sahadan {$homeScore}-{$awayScore} beraberlikle ayrıldı.\n\n"
                  . "Son düdüğe kadar heyecanın eksik olmadığı maçta iki takım da 1'er puanı hanesine yazdırdı."
                  . ($mvp ? "\n\nMaçın adamı seçilen {$mvp}, performansıyla takdir topladı." : '')
                  . "\n\nŞehir puan durumundaki güncel sıralamayı Nisoya Halı Saha sayfasından inceleyebilirsiniz.";
        }

        return [
            'title' => $title,
            'summary' => $summary,
            'body' => $body,
        ];
    }

    /**
     * WhatsApp paylaşım metnini oluşturur.
     */
    public function generateWhatsAppShareText(FootballMatch $match): string
    {
        $homeName = $match->homeTeam?->name ?: 'Ev Sahibi';
        $awayName = $match->awayTeam?->name ?: 'Deplasman';
        $homeScore = (int) $match->home_score;
        $awayScore = (int) $match->away_score;
        $venue = $match->venueDisplay();
        $city = $match->city;
        $matchUrl = route('football.matches.show', ['city' => Str::slug($city), 'match' => $match->id]);

        return "⚽ {$city}'de Halı Saha Maç Sonucu!\n\n"
             . "🏆 {$homeName} {$homeScore} — {$awayScore} {$awayName}\n"
             . "📍 {$venue}\n\n"
             . "📰 Maç haberi, goller ve lig durumu Nisoya'da:\n"
             . "{$matchUrl}";
    }
}
