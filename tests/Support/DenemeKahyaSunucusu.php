<?php

namespace Tests\Support;

use App\Mcp\Sunucular\KahyaSunucusu;
use Laravel\Mcp\Server\Tool;

/**
 * SADECE TEST İÇİN — gerçek sunucuya bilerek eklenmeyen {@see YazmayaCalisanArac}'ı
 * kaydeder.
 *
 * NEDEN AYRI SUNUCU GEREKTİ: `KahyaSunucusu::tool(new YazmayaCalisanArac)`
 * çağrısı çalışmıyor. Paketin `CallTool` metodu aracı ADIYLA sunucunun kayıt
 * listesinden çözüyor; kayıtlı olmayan bir örnek verildiğinde
 * "Tool [...] not found" JSON-RPC hatası dönüyor.
 *
 * Bu tuzak sinsi: dönen şey yine bir HATA olduğu için `assertHasErrors()`
 * yeşil yanar ve test "yazma engellendi" sanır — oysa bekçi hiç devreye
 * girmemiştir. Bu yüzden ilgili test hatanın METNİNİ de doğruluyor.
 */
class DenemeKahyaSunucusu extends KahyaSunucusu
{
    /** @var array<int, class-string<Tool>> */
    protected array $tools = [
        YazmayaCalisanArac::class,
    ];
}
