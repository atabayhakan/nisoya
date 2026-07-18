<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Konuşma katılımcı sırasını normalize et: user_one_id her zaman KÜÇÜK,
 * user_two_id BÜYÜK olur. Böylece mevcut unique index
 * (listing_id, user_one_id, user_two_id) iki yönü de korur — (A,B) ve (B,A)
 * artık aynı satıra normalize olduğu için yarış durumunda ikinci ekleme
 * DB tarafından reddedilir (harici inceleme #M4).
 *
 * Uygulama tarafı da normalize edecek şekilde güncellendi (bkz.
 * Conversation::findOrCreateBetween). Bu migration YALNIZCA mevcut satırları
 * düzeltir. Prod'da 0 konuşma var — no-op; kod tutarlılık/portability için.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('conversations')->orderBy('id')->get(['id', 'listing_id', 'user_one_id', 'user_two_id']);

        $seen = [];

        foreach ($rows as $row) {
            $low = min($row->user_one_id, $row->user_two_id);
            $high = max($row->user_one_id, $row->user_two_id);
            $key = $row->listing_id.':'.$low.':'.$high;

            if (isset($seen[$key])) {
                // Çift-yön kopya: mesajları kalıcı konuşmaya taşı, kopyayı sil.
                DB::table('messages')->where('conversation_id', $row->id)
                    ->update(['conversation_id' => $seen[$key]]);
                DB::table('conversations')->where('id', $row->id)->delete();

                continue;
            }

            $seen[$key] = $row->id;

            if ($row->user_one_id !== $low || $row->user_two_id !== $high) {
                DB::table('conversations')->where('id', $row->id)
                    ->update(['user_one_id' => $low, 'user_two_id' => $high]);
            }
        }
    }

    public function down(): void
    {
        // Normalize edilmiş sıra geri alınamaz (orijinal sıra bilgisi kaybolur)
        // ve zaten kanonik biçim tercih edilir — no-op.
    }
};
