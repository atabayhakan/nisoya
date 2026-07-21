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
 */
class AdminRecover extends Command
{
    protected $signature = 'admin:recover
        {email? : Kurtarılacak yöneticinin e-postası}
        {--password= : Yeni parola (verilmezse rastgele güçlü parola üretilir)}
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

        $user->forceFill([
            'password' => Hash::make($password),
            'role' => UserRole::Admin,
            'status' => UserStatus::Aktif,
            'remember_token' => Str::random(60),
        ])->save();

        activity('auth')
            ->performedOn($user)
            ->withProperties(['via' => 'admin:recover (CLI)'])
            ->log('Cam-kır: yönetici parolası sıfırlandı');

        $this->info('✓ Hesap kurtarıldı: '.$user->email);
        $this->line('  Rol: Yönetici · Durum: Aktif');
        $this->line('  Yeni parola: '.$password);
        $this->warn('  Bu parolayı giriş yaptıktan sonra mutlaka değiştir.');

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
            $rows[] = [$admin->email, $admin->status->value];
        }

        $this->table(['E-posta', 'Durum'], $rows);
    }
}
