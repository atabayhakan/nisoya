<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bir kullanıcı en fazla BİR şirket sahibi olabilir (User::company() bir HasOne
 * — birden fazla şirket varsa sessizce yalnızca birini döndürüyordu; harici
 * inceleme #M4). DB seviyesinde tekilliği garanti et.
 *
 * Prod'da yinelenen sahiplik olmadığı doğrulandı (0). Idempotent: index zaten
 * varsa atla.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('companies', 'companies_user_id_unique')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->unique('user_id', 'companies_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasIndex('companies', 'companies_user_id_unique')) {
                $table->dropUnique('companies_user_id_unique');
            }
        });
    }
};
