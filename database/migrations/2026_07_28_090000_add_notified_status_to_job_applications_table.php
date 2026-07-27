<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            // "Adaya EN SON BİLDİRİLEN durum". status'ten ayrı tutulur çünkü
            // artık her durum değişikliği anında e-posta üretmiyor: bildirim
            // gecikmeli bir işle gidiyor ve iş çalıştığında GÜNCEL durumu
            // yeniden okuyor. Bu iki alanın farkı "adaya söylenmesi gereken
            // ama henüz söylenmemiş şey" demek.
            $table->string('notified_status')->nullable()->after('status');
        });

        // Eski kod HER durum değişikliğinde bildirim gönderiyordu; dolayısıyla
        // mevcut satırlarda adaya en son bildirilen durum = güncel durumdur.
        // Bu geriye dönük doldurma olmadan, deploy'dan sonraki ilk sürükleme
        // adaylara zaten bildikleri durumu tekrar e-postalardı.
        DB::table('job_applications')->update(['notified_status' => DB::raw('status')]);
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn('notified_status');
        });
    }
};
