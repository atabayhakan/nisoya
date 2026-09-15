<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ImportGlobalGeoCatalog extends Command
{
    protected $signature = 'global-command:import-geo {--dry-run : Yalnız katalog kapsamını göster}';

    protected $description = 'Sürümlü ISO/CLDR kataloğundan bütün ülkeleri, bölgeleri, dilleri ve para birimlerini tamamlar.';

    public function handle(): int
    {
        $catalog = json_decode(file_get_contents(database_path('data/global-geo/catalog.json')), true, flags: JSON_THROW_ON_ERROR);
        $codes = array_column($catalog['countries'], 'code');
        if (count($codes) !== count(array_unique($codes)) || count($codes) < 200) {
            $this->error('Ülke kataloğu eksik veya tekrarlı.');

            return self::FAILURE;
        }
        $this->info($catalog['version'].': '.count($codes).' ISO ülke/bölgesi, '.count($catalog['regions']).' coğrafi küme.');
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }
        DB::transaction(function () use ($catalog, $codes): void {
            foreach ($catalog['currencies'] as $row) {
                Currency::firstOrCreate(['code' => $row['code']], $row + ['is_active' => true]);
            }
            foreach ($catalog['languages'] as $row) {
                DB::table('geo_languages')->upsert($row, ['tag'], ['name_tr']);
            }
            foreach ($catalog['countries'] as $row) {
                // Preserve existing editorial names, ordering and intentionally inactive countries.
                Country::firstOrCreate(['code' => $row['code']], [
                    'name_tr' => $row['name_tr'], 'emoji' => $row['emoji'],
                    'default_currency' => $row['currencies'][0] ?? null, 'is_active' => true, 'sort_order' => 1000,
                ]);
                DB::table('country_code_metadata')->upsert([
                    'country_code' => $row['code'], 'code_system' => 'iso-3166-1', 'name_en' => $row['name_en'],
                    'alpha3' => $row['alpha3'], 'numeric' => $row['numeric'], 'currencies' => json_encode($row['currencies']),
                    'source_version' => $catalog['version'], 'created_at' => now(), 'updated_at' => now(),
                ], ['country_code'], ['code_system', 'name_en', 'alpha3', 'numeric', 'currencies', 'source_version', 'updated_at']);
                DB::table('geo_country_language')->where('country_code', $row['code'])->delete();
                foreach ($row['languages'] as $index => $tag) {
                    DB::table('geo_country_language')->insert(['country_code' => $row['code'], 'language_tag' => $tag, 'is_default' => $index === 0]);
                }
            }
            foreach (Country::whereNotIn('code', $codes)->pluck('code') as $code) {
                DB::table('country_code_metadata')->updateOrInsert(['country_code' => $code], [
                    'code_system' => 'local-extension', 'source_version' => $catalog['version'], 'updated_at' => now(),
                ]);
            }
            foreach ($catalog['regions'] as $row) {
                DB::table('geo_regions')->updateOrInsert(['slug' => $row['slug']], [
                    'name_tr' => $row['name_tr'], 'kind' => $row['kind'], 'source_version' => $catalog['version'],
                    'is_active' => true, 'updated_at' => now(),
                ]);
                $id = DB::table('geo_regions')->where('slug', $row['slug'])->value('id');
                DB::table('geo_region_country')->where('geo_region_id', $id)->delete();
                foreach ($row['countries'] as $code) {
                    DB::table('geo_region_country')->insert(['geo_region_id' => $id, 'country_code' => $code]);
                }
            }
        });
        Cache::forget(Country::ACTIVE_LIST_CACHE_KEY);
        Cache::forever('gcc:catalog-revision', $catalog['version'].':'.now()->timestamp);
        $this->info('Katalog tamamlandı; mevcut ülke kimlikleri ve yerel uzantılar korundu.');

        return self::SUCCESS;
    }
}
