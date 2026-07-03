<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('method');
            $table->string('detail', 255)->nullable();
            $table->string('qr_path')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'method']);
        });

        // Faz 0'da users.payment_methods (JSON) içine kaydedilmiş seçimleri
        // yeni tabloya taşı (yalnızca yöntem adı, link/QR yok — kullanıcı
        // sonradan ekleyebilir).
        $rows = DB::table('users')->whereNotNull('payment_methods')->select('id', 'payment_methods')->get();
        $now = now();
        foreach ($rows as $row) {
            $methods = json_decode($row->payment_methods, true) ?? [];
            foreach ($methods as $method) {
                DB::table('payment_links')->insertOrIgnore([
                    'user_id' => $row->id,
                    'method' => $method,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_links');
    }
};
