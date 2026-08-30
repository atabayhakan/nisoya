<?php

namespace Database\Seeders;

use App\Enums\FootballLevel;
use App\Enums\FootballMatchStatus;
use App\Enums\FootballMemberStatus;
use App\Enums\FootballPosition;
use App\Enums\FootballRequestType;
use App\Enums\FootballResultStatus;
use App\Models\FootballMatch;
use App\Models\FootballPlayerProfile;
use App\Models\FootballPlayerRequest;
use App\Models\FootballTeam;
use App\Models\FootballTeamMember;
use App\Models\FootballVenue;
use App\Models\FootballVenueReview;
use App\Models\User;
use App\Services\Football\FootballNewsService;
use App\Services\Football\FootballStatsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FootballSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Oyuncular ve Kaptanlar
        $kaptan1 = User::firstOrCreate(
            ['email' => 'kaptan.berlin@nisoya.com'],
            [
                'name' => 'Emre Yılmaz',
                'username' => 'emreyilmaz',
                'password' => Hash::make('Password123!'),
                'city' => 'Berlin',
                'country_code' => 'DE',
                'status' => 'aktif',
            ]
        );

        $kaptan2 = User::firstOrCreate(
            ['email' => 'kaptan.kreuzberg@nisoya.com'],
            [
                'name' => 'Caner Demir',
                'username' => 'canerdmr',
                'password' => Hash::make('Password123!'),
                'city' => 'Berlin',
                'country_code' => 'DE',
                'status' => 'aktif',
            ]
        );

        $kaptan3 = User::firstOrCreate(
            ['email' => 'kaptan.koln@nisoya.com'],
            [
                'name' => 'Burak Çelik',
                'username' => 'burakcelik',
                'password' => Hash::make('Password123!'),
                'city' => 'Köln',
                'country_code' => 'DE',
                'status' => 'aktif',
            ]
        );

        $oyuncu1 = User::firstOrCreate(
            ['email' => 'hakan.kaleci@nisoya.com'],
            [
                'name' => 'Hakan Aktaş',
                'username' => 'hakanaktas',
                'password' => Hash::make('Password123!'),
                'city' => 'Berlin',
                'country_code' => 'DE',
                'status' => 'aktif',
            ]
        );

        // 2. Futbol Profilleri
        FootballPlayerProfile::updateOrCreate(
            ['user_id' => $kaptan1->id],
            [
                'city' => 'Berlin',
                'country_code' => 'DE',
                'positions' => ['orta_saha', 'forvet'],
                'preferred_foot' => 'sag',
                'level' => FootballLevel::Iyi,
                'bio' => 'Berlin içi haftalık düzenli halı saha maçları organize ediyorum. Tempolu maç severiz.',
                'is_looking_for_team' => false,
                'is_looking_for_match' => true,
                'matches_played' => 12,
                'goals' => 18,
                'rating' => 4.80,
                'ratings_count' => 5,
            ]
        );

        FootballPlayerProfile::updateOrCreate(
            ['user_id' => $oyuncu1->id],
            [
                'city' => 'Berlin',
                'country_code' => 'DE',
                'positions' => ['kaleci'],
                'preferred_foot' => 'sag',
                'level' => FootballLevel::Ileri,
                'bio' => '10 yıl kulüp altyapısında kalecilik yaptım. Eksik kaleci arayan takımlara yardıma gelebilirim.',
                'is_looking_for_team' => true,
                'is_looking_for_match' => true,
                'matches_played' => 20,
                'rating' => 4.95,
                'ratings_count' => 8,
            ]
        );

        // 3. Halı Sahalar
        $saha1 = FootballVenue::firstOrCreate(
            ['slug' => 'soccerworld-berlin'],
            [
                'created_by_id' => $kaptan1->id,
                'name' => 'Soccerworld Berlin',
                'city' => 'Berlin',
                'country_code' => 'DE',
                'address' => 'Richard-Tauber-Damm 36, 12277 Berlin',
                'phone' => '+49 30 747474',
                'pitch_type' => 'kapali',
                'surface_type' => 'suni_cim',
                'features' => ['soyunma_odasi', 'dus', 'otopark', 'kafe', 'gece_aydinlatmasi'],
                'opening_hours' => '09:00 - 24:00',
                'price_info' => 'Saatlik 85€ - 110€',
                'rating' => 4.85,
                'reviews_count' => 4,
                'is_active' => true,
                'is_verified' => true,
            ]
        );

        $saha2 = FootballVenue::firstOrCreate(
            ['slug' => 'arena-sport-koln'],
            [
                'created_by_id' => $kaptan3->id,
                'name' => 'Arena Sport Köln',
                'city' => 'Köln',
                'country_code' => 'DE',
                'address' => 'Oskar-Jäger-Straße 173, 50825 Köln',
                'phone' => '+49 221 543210',
                'pitch_type' => 'kapali',
                'surface_type' => 'suni_cim',
                'features' => ['soyunma_odasi', 'dus', 'otopark', 'yelek_top'],
                'price_info' => 'Saatlik 90€',
                'rating' => 4.70,
                'reviews_count' => 2,
                'is_active' => true,
                'is_verified' => true,
            ]
        );

        FootballVenueReview::firstOrCreate(
            ['venue_id' => $saha1->id, 'user_id' => $kaptan2->id],
            [
                'rating' => 5,
                'sub_ratings' => ['saha_kalitesi' => 5, 'temizlik' => 5, 'dus_soyunma' => 4, 'fiyat_performans' => 5],
                'comment' => 'Zemin mükemmel, duşlar sıcak ve temiz. Berlin\'in en iyi kapalı sahası.',
                'status' => 'yayinda',
            ]
        );

        // 4. Takımlar
        $team1 = FootballTeam::firstOrCreate(
            ['slug' => 'berlin-hilalspor'],
            [
                'user_id' => $kaptan1->id,
                'name' => 'Berlin Hilalspor',
                'city' => 'Berlin',
                'country_code' => 'DE',
                'primary_kit_color' => 'Kırmızı',
                'secondary_kit_color' => 'Beyaz',
                'level' => FootballLevel::Iyi,
                'description' => '2024 yılında Berlin\'de kurulan dostluk ve rekabet odaklı halı saha takımı.',
                'is_verified' => true,
                'is_active' => true,
            ]
        );

        FootballTeamMember::firstOrCreate(
            ['team_id' => $team1->id, 'user_id' => $kaptan1->id],
            [
                'role' => 'captain',
                'status' => FootballMemberStatus::Aktif->value,
                'jersey_number' => 10,
                'primary_position' => FootballPosition::OrtaSaha->value,
                'joined_at' => now(),
            ]
        );

        FootballTeamMember::firstOrCreate(
            ['team_id' => $team1->id, 'user_id' => $oyuncu1->id],
            [
                'role' => 'player',
                'status' => FootballMemberStatus::Aktif->value,
                'jersey_number' => 1,
                'primary_position' => FootballPosition::Kaleci->value,
                'joined_at' => now(),
            ]
        );

        $team2 = FootballTeam::firstOrCreate(
            ['slug' => 'kreuzberg-united'],
            [
                'user_id' => $kaptan2->id,
                'name' => 'Kreuzberg United',
                'city' => 'Berlin',
                'country_code' => 'DE',
                'primary_kit_color' => 'Siyah',
                'secondary_kit_color' => 'Sarı',
                'level' => FootballLevel::Iyi,
                'description' => 'Kreuzberg merkezli genç ve dinamik futbol grubu.',
                'is_verified' => true,
                'is_active' => true,
            ]
        );

        FootballTeamMember::firstOrCreate(
            ['team_id' => $team2->id, 'user_id' => $kaptan2->id],
            [
                'role' => 'captain',
                'status' => FootballMemberStatus::Aktif->value,
                'jersey_number' => 9,
                'primary_position' => FootballPosition::Forvet->value,
                'joined_at' => now(),
            ]
        );

        $team3 = FootballTeam::firstOrCreate(
            ['slug' => 'koln-yildizlar-fc'],
            [
                'user_id' => $kaptan3->id,
                'name' => 'Köln Yıldızlar FC',
                'city' => 'Köln',
                'country_code' => 'DE',
                'primary_kit_color' => 'Mavi',
                'secondary_kit_color' => 'Beyaz',
                'level' => FootballLevel::Orta,
                'description' => 'Köln ve çevresindeki gurbetçilerin buluştuğu haftalık maç ekibi.',
                'is_verified' => true,
                'is_active' => true,
            ]
        );

        FootballTeamMember::firstOrCreate(
            ['team_id' => $team3->id, 'user_id' => $kaptan3->id],
            [
                'role' => 'captain',
                'status' => FootballMemberStatus::Aktif->value,
                'jersey_number' => 7,
                'primary_position' => FootballPosition::Kanat->value,
                'joined_at' => now(),
            ]
        );

        // 5. Doğrulanmış Maç ve Spor Haberi
        $newsService = app(FootballNewsService::class);
        $statsService = app(FootballStatsService::class);

        $match1 = FootballMatch::firstOrCreate(
            [
                'home_team_id' => $team1->id,
                'away_team_id' => $team2->id,
                'match_date' => now()->subDays(2)->setTime(20, 0),
            ],
            [
                'venue_id' => $saha1->id,
                'city' => 'Berlin',
                'country_code' => 'DE',
                'status' => FootballMatchStatus::Oynandi->value,
                'home_score' => 6,
                'away_score' => 4,
                'result_status' => FootballResultStatus::Dogrulandi->value,
                'result_submitted_by_id' => $kaptan1->id,
                'result_verified_by_id' => $kaptan2->id,
                'mvp_player_id' => $kaptan1->id,
                'home_scorers' => [
                    ['user_id' => $kaptan1->id, 'name' => 'Emre Yılmaz', 'goals' => 3],
                ],
                'away_scorers' => [
                    ['user_id' => $kaptan2->id, 'name' => 'Caner Demir', 'goals' => 2],
                ],
                'is_featured' => true,
            ]
        );

        $news = $newsService->generateMatchNews($match1);
        $match1->update([
            'news_title' => $news['title'],
            'news_summary' => $news['summary'],
            'news_body' => $news['body'],
            'news_generated_at' => now()->subDays(2),
        ]);

        $statsService->applyVerifiedMatchStats($match1);

        // 6. Oyuncu İlanı
        FootballPlayerRequest::firstOrCreate(
            [
                'user_id' => $kaptan1->id,
                'city' => 'Berlin',
                'type' => FootballRequestType::OyuncuAraniyor->value,
            ],
            [
                'team_id' => $team1->id,
                'country_code' => 'DE',
                'match_time' => now()->addDays(3)->setTime(21, 0),
                'venue_name' => 'Soccerworld Berlin',
                'needed_count' => 2,
                'level' => FootballLevel::Orta->value,
                'positions' => ['defans', 'orta_saha'],
                'description' => 'Cuma akşamı saat 21:00 maçımız için 2 eksik oyuncumuz var. Tempolu ve keyifli bir maç olacak, katılmak isteyenler hemen yazsın!',
                'is_active' => true,
            ]
        );
    }
}
