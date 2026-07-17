<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Yakınlaştırmalı avatar hizalama: kullanıcı modalda kaydır+zum yapar,
     * kaydederken sunucu orijinalden (avatar_path) KARE bir kırpım üretir
     * (avatar_cropped_path) — gösterim her yerde bu kare dosyayı kullanır,
     * transform/odak matematiği tekrarlanmaz. crop_x/y/size, orijinal görselin
     * piksel koordinatlarında kırpım karesidir; modal yeniden açıldığında
     * editör durumunu (pan+zum) geri kurmak için saklanır.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_cropped_path')->nullable()->after('avatar_path');
            $table->unsignedInteger('avatar_crop_x')->nullable()->after('avatar_focal_y');
            $table->unsignedInteger('avatar_crop_y')->nullable()->after('avatar_crop_x');
            $table->unsignedInteger('avatar_crop_size')->nullable()->after('avatar_crop_y');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_cropped_path', 'avatar_crop_x', 'avatar_crop_y', 'avatar_crop_size']);
        });
    }
};
