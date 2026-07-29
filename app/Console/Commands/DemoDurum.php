<?php

namespace App\Console\Commands;

use App\Services\Demo\DemoDefteri;
use App\Services\Demo\DemoTemizleyici;
use Illuminate\Console\Command;

/**
 * Hangi demo partileri var, içlerinde ne var, geride artık kaldı mı.
 *
 * "Artık" satırı bu komutun asıl işi: defterde olmayan ama `is_demo` işaretli
 * bir kayıt varsa bir yerde bir şey kaçmış demektir ve bunu SÖYLEMESİ gerekir.
 */
class DemoDurum extends Command
{
    protected $signature = 'demo:durum';

    protected $description = 'Demo (örnek) veri partilerini ve geride kalan artıkları listeler';

    public function handle(DemoDefteri $defter, DemoTemizleyici $temizleyici): int
    {
        $partiler = $defter->partiler();

        if ($partiler === []) {
            $this->components->info('Kayıtlı demo partisi yok.');
        } else {
            $this->table(
                ['Parti', 'Oluşturuldu', 'Kayıt', 'Döküm'],
                array_map(fn (array $p): array => [
                    $p['parti'],
                    $p['olusturuldu'] ?? '—',
                    $p['adet'],
                    collect($p['dokum'])->map(fn (int $a, string $ad): string => "{$ad}:{$a}")->implode(' '),
                ], $partiler),
            );
        }

        $artik = $temizleyici->artikSayisi();

        if ($artik > 0) {
            $this->components->warn(
                "Defterde OLMAYAN {$artik} işaretli demo kaydı var. ".
                'Bunlar `demo:sil` ile temizlenmez — elle bakılmalı.'
            );

            return self::FAILURE;
        }

        $this->components->info('Defter dışı artık yok.');

        return self::SUCCESS;
    }
}
