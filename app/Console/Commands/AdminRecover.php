<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * "Cam kır" son çare (Faz 1 · G2): parola, 2FA ve hesap kurtarma kodlarının
 * hepsi kaybolduğunda, sunucu erişimiyle bir yöneticiyi geri kazandırır.
 * Parolayı sıfırlar, hesabı Yönetici + Aktif yapar ve yeni parolayı yazar.
 * Her işlem İşlem Geçmişi'ne (activity log) kaydedilir.
 *
 * ---------------------------------------------------------------------------
 * `--iki-faktor-sifirla` NEDEN EKLENDİ (2026-08-05)
 *
 * Yukarıdaki açıklama "2FA ... kaybolduğunda" diyordu ama komut 2FA alanlarına
 * HİÇ dokunmuyordu. Zorunluluk gelmeden önce bu yalnızca bir belge hatasıydı;
 * `YonetimIkiFaktorZorunlu` devreye girince gerçek bir kilitlenmeye dönüştü:
 * telefonunu ve yedek kodlarını kaybeden tek yönetici, parolasını sıfırlasa
 * bile panele giremezdi — çünkü artık 2FA şart.
 *
 * Sıfırlama BİLİNÇLE opsiyona bağlı: rutin bir parola kurtarmanın sessizce
 * ikinci faktörü düşürmesi, kurtarma komutunu bir güvenlik zaafına çevirirdi.
 */
class AdminRecover extends Command
{
    protected $signature = 'admin:recover
        {email? : Kurtarılacak yöneticinin e-postası}
        {--password= : Yeni parola (verilmezse rastgele güçlü parola üretilir)}
        {--iki-faktor-sifirla : İki faktörlü doğrulamayı da kaldır (telefon + yedek kodlar kaybolduysa)}
        {--list : Yalnızca mevcut yöneticileri listele}';

    protected $description = 'Son çare: bir yöneticinin parolasını sıfırlar ve hesabı Yönetici + Aktif yapar';

    public function handle(): int
    {
        if ($this->option('list')) {
            $this->listAdmins();

            return self::SUCCESS;
        }

        $email = $this->argument('email') ?: $this->ask('Kurtarılacak yöneticinin e-postası');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("Bu e-postayla kullanıcı bulunamadı: {$email}");

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: Str::password(16));
        $ikiFaktorSifirla = (bool) $this->option('iki-faktor-sifirla');

        $alanlar = [
            'password' => Hash::make($password),
            'role' => UserRole::Admin,
            'status' => UserStatus::Aktif,
            'remember_token' => Str::random(60),
        ];

        if ($ikiFaktorSifirla) {
            $alanlar['two_factor_secret'] = null;
            $alanlar['two_factor_confirmed_at'] = null;
            $alanlar['two_factor_recovery_codes'] = null;
        }

        $user->forceFill($alanlar)->save();

        activity('auth')
            ->performedOn($user)
            ->withProperties(['via' => 'admin:recover (CLI)', 'iki_faktor_sifirlandi' => $ikiFaktorSifirla])
            ->log($ikiFaktorSifirla
                ? 'Cam-kır: yönetici parolası ve iki faktörlü doğrulama sıfırlandı'
                : 'Cam-kır: yönetici parolası sıfırlandı');

        $this->info('✓ Hesap kurtarıldı: '.$user->email);
        $this->line('  Rol: Yönetici · Durum: Aktif');
        $this->line('  Yeni parola: '.$password);
        $this->warn('  Bu parolayı giriş yaptıktan sonra mutlaka değiştir.');

        if ($ikiFaktorSifirla) {
            $this->line('  İki faktörlü doğrulama: KALDIRILDI');
            $this->warn('  Panele girer girmez yeniden kuracaksın — yönetim paneli 2FA olmadan açılmaz.');
        } elseif ($user->hasTwoFactorEnabled()) {
            // Sessiz kalırsa, telefonunu kaybetmiş biri parolayı sıfırlayıp
            // yine giremeyince komutun bozuk olduğunu sanır.
            $this->line('  İki faktörlü doğrulama: hâlâ AÇIK — girişte kod istenecek.');
            $this->line('  Telefona da erişemiyorsan: --iki-faktor-sifirla ile tekrar çalıştır.');
        }

        return self::SUCCESS;
    }

    private function listAdmins(): void
    {
        $admins = User::query()
            ->where('role', UserRole::Admin)
            ->orderBy('email')
            ->get();

        if ($admins->isEmpty()) {
            $this->warn('Hiç yönetici yok!');

            return;
        }

        $rows = [];
        foreach ($admins as $admin) {
            // 2FA sütunu: zorunluluk devreye girdikten sonra "bu hesap panele
            // girebilir mi?" sorusunun cevabı artık role bakarak verilemiyor.
            $rows[] = [$admin->email, $admin->status->value, $admin->hasTwoFactorEnabled() ? 'açık' : 'KAPALI'];
        }

        $this->table(['E-posta', 'Durum', '2FA'], $rows);

        if ($admins->count() === 1) {
            $this->warn('Tek yönetici var. Bu hesaba ulaşılamadığı gün panel kilitlenir — ikinci bir yönetici tanımla.');
        }
    }
}
