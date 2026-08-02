<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * laragear/webauthn → laravel/passkeys geçişi (2026-08-02).
 *
 * Eski paket resmen terk edildi ve Packagist halef olarak laravel/passkeys'i
 * gösteriyor; geçiş, canlıda 0 kayıtlı passkey varken yapıldı — taşınacak
 * veri YOK, tablo doğrudan düşürülür. Eski create-migration dosyası da
 * silindi (laragear sınıfına referans veriyordu; paket kalkınca taze
 * kurulumda migrate patlardı) — bu yüzden dropIfExists: taze kurulumda
 * tablo hiç var olmaz, mevcut ortamlarda düşer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('webauthn_credentials');
    }

    public function down(): void
    {
        // Bilinçli boş: geri dönüş, terk edilmiş pakete dönmek demek olurdu.
        // Passkey'ler artık laravel/passkeys'in 'passkeys' tablosunda yaşıyor.
    }
};
