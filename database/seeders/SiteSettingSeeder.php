<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Support\Settings;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('site_defaults.fields') as $key => $meta) {
            SiteSetting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $meta['default'] ?? null, 'group' => $meta['group'] ?? 'genel'],
            );
        }

        Settings::forget();
    }
}
