<?php

use App\Support\GlobalCommand\CityName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['listings', 'job_listings', 'users', 'diaspora_accounts', 'diaspora_reels', 'content_assessments'];

    public function up(): void
    {
        Schema::create('city_name_aliases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->string('name');
            $table->string('normalized_name')->index();
            $table->boolean('is_canonical')->default(false);
            $table->timestamps();
            $table->unique(['city_id', 'normalized_name']);
        });
        DB::table('cities')->orderBy('id')->chunkById(250, function ($cities): void {
            foreach ($cities as $city) {
                DB::table('city_name_aliases')->insert(['city_id' => $city->id, 'name' => $city->name,
                    'normalized_name' => CityName::normalize($city->name), 'is_canonical' => true,
                    'created_at' => now(), 'updated_at' => now()]);
            }
        });
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->char('city_key', 64)->nullable();
                $table->index(['country_code', 'city_key']);
            });
            DB::table($name)->whereNotNull('city')->orderBy('id')->chunkById(250, function ($rows) use ($name): void {
                foreach ($rows as $row) {
                    DB::table($name)->where('id', $row->id)->update(['city_key' => CityName::key($row->city)]);
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropIndex(['country_code', 'city_key']);
                $table->dropColumn('city_key');
            });
        }
        Schema::dropIfExists('city_name_aliases');
    }
};
