<?php

namespace Database\Seeders;

use App\Models\SssSorusu;
use Illuminate\Database\Seeder;

/**
 * StaticPagesSeeder'ın eski `sss` sayfa bloğunda gömülü duran 5 soru — artık
 * yapılandırılmış, panelden düzenlenebilir ve Nisoya AI aramasının
 * sorgulayabildiği kayıtlar (bkz. SssDogalDilArama).
 *
 * ReferenceDataSeeder'a EKLENMEZ (o dosyanın kendi docblock'undaki kural):
 * admin panelden düzenlenen içerik deploy'da ezilmez. Yalnız DatabaseSeeder
 * (yerel/test) üzerinden çalışır; canlıya elle BİR KEZ
 * `db:seed --class=SssSorulariSeeder --force` ile taşınır.
 */
class SssSorulariSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->sorular() as $soru) {
            SssSorusu::query()->firstOrCreate(['soru' => $soru['soru']], $soru);
        }
    }

    /** @return array<int, array<string, mixed>> */
    protected function sorular(): array
    {
        return [
            [
                'soru' => 'Nisoya ücretli mi?',
                'cevap' => 'Hayır, kayıt olmak ve ilan vermek tamamen ücretsiz. İleride isteğe bağlı öne çıkarma seçenekleri eklenebilir.',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'soru' => 'Ödeme Nisoya üzerinden mi yapılıyor?',
                'cevap' => 'Hayır. Nisoya bir ilan ve iletişim platformudur. Ödeme ve anlaşma doğrudan kullanıcılar arasında yapılır.',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'soru' => 'Türkiye\'den kullanabilir miyim?',
                'cevap' => 'Nisoya yurt dışında yaşayan Türklere yöneliktir ve Türk Lirası kullanmaz. Fiyatlar bulunduğun ülkenin para biriminde gösterilir.',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'soru' => 'Bir ilana nasıl güvenirim?',
                'cevap' => 'Satıcının profilini, değerlendirmelerini ve puanını incele. Şüpheli durumda "şikayet et" özelliğini kullan.',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'soru' => 'İlanım neden görünmüyor?',
                'cevap' => 'İlanlar genelde anında yayınlanır. Kurallara aykırı bulunan ilanlar yöneticiler tarafından pasifleştirilebilir.',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];
    }
}
