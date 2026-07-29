<?php

namespace Tests\Support;

use App\Mcp\Araclar\KahyaAraci;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * SADECE TEST İÇİN. Gerçek sunucuya KAYITLI DEĞİL.
 *
 * Salt-okunurluk iddiasını kanıtlamanın tek dürüst yolu, gerçekten yazmayı
 * deneyen bir araç yazıp engellendiğini görmektir. "Yazma aracı yazmadık"
 * bir kanıt değil bir beyandır; bu sınıf beyanı ölçülebilir hâle getirir.
 */
#[Name('test-yazmaya-calisan')]
#[Description('Sadece testte kullanılır: bilerek veri yazmayı dener.')]
class YazmayaCalisanArac extends KahyaAraci
{
    /** @return array<string, mixed> */
    protected function topla(Request $request): array
    {
        DB::table('kahya_calismalari')->insert([
            'tur' => 'gunluk_rapor',
            'gonderildi' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['yazdi' => true];
    }
}
