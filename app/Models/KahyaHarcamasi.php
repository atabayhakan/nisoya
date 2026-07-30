<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Ai\Responses\Data\Usage;

/**
 * Kâhya'nın LLM harcama defterinin bir satırı — gerekçe migration'da.
 *
 * @property string $kaynak
 * @property string $saglayici
 * @property string $model
 * @property int $girdi_token
 * @property int $cikti_token
 * @property int $onbellek_okuma_token
 * @property int $onbellek_yazma_token
 */
class KahyaHarcamasi extends Model
{
    protected $table = 'kahya_harcamalar';

    protected $fillable = [
        'kaynak', 'saglayici', 'model',
        'girdi_token', 'cikti_token',
        'onbellek_okuma_token', 'onbellek_yazma_token',
    ];

    /**
     * laravel/ai kullanım nesnesinden bir defter satırı yazar.
     *
     * Deftere yazamamak sohbeti KIRMAMALI — çağıran taraf `rescue()` ile
     * sarmalar; burada yalnız eşleme var.
     */
    public static function kaydet(string $kaynak, string $saglayici, string $model, Usage $kullanim): self
    {
        return self::create([
            'kaynak' => $kaynak,
            'saglayici' => $saglayici,
            'model' => $model,
            'girdi_token' => $kullanim->promptTokens,
            'cikti_token' => $kullanim->completionTokens,
            'onbellek_okuma_token' => $kullanim->cacheReadInputTokens,
            'onbellek_yazma_token' => $kullanim->cacheWriteInputTokens,
        ]);
    }

    /** Bu ayın toplamları — panel sayacı için. @return array{girdi: int, cikti: int, adet: int} */
    public static function buAy(): array
    {
        $buAy = self::query()->where('created_at', '>=', now()->startOfMonth());

        return [
            'girdi' => (int) (clone $buAy)->sum('girdi_token'),
            'cikti' => (int) (clone $buAy)->sum('cikti_token'),
            'adet' => (clone $buAy)->count(),
        ];
    }
}
