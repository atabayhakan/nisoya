<?php

// Offline build from downloaded upstream snapshots; never runs during a web request.
$root = dirname(__DIR__);
$input = $root.'/storage/app/global-catalog-build';
$read = fn (string $file) => json_decode(file_get_contents($input.'/'.$file), true, flags: JSON_THROW_ON_ERROR);
$iso = $read('iso.json')['3166-1'];
$names = $read('territories.json')['main']['tr']['localeDisplayNames']['territories'];
$languages = $read('languages.json')['main']['tr']['localeDisplayNames']['languages'];
$money = $read('currencies.json')['main']['tr']['numbers']['currencies'];
$currencyData = $read('currencyData.json')['supplemental']['currencyData']['region'];
$info = $read('territoryInfo.json')['supplemental']['territoryInfo'];
$containment = $read('territoryContainment.json')['supplemental']['territoryContainment'];
$codes = array_column($iso, 'alpha_2');
$date = '2026-09-15';
$flatten = function (string $key) use (&$flatten, $containment, $codes): array {
    if (in_array($key, $codes, true)) {
        return [$key];
    }
    $result = [];
    foreach ($containment[$key]['_contains'] ?? [] as $child) {
        $result = array_merge($result, $flatten($child));
    }

    return array_values(array_unique($result));
};
$regions = [];
foreach ($containment as $key => $value) {
    $key = (string) $key;
    if (! preg_match('/^\d{3}$/', $key) || $key === '001') {
        continue;
    }
    $members = $flatten($key);
    if ($members !== []) {
        $regions[] = ['slug' => 'm49-'.$key, 'name_tr' => $names[$key] ?? $key,
            'kind' => in_array($key, ['002', '019', '142', '150', '009'], true) ? 'continent' : 'un_m49', 'countries' => $members];
    }
}
$countryRows = [];
$currencyRows = [];
$languageRows = [];
foreach ($iso as $country) {
    $code = $country['alpha_2'];
    $currencies = [];
    foreach ($currencyData[$code] ?? [] as $period) {
        foreach ($period as $currency => $interval) {
            if (($interval['_tender'] ?? 'true') !== 'false' && ($interval['_from'] ?? '0000-01-01') <= $date
                && (! isset($interval['_to']) || $interval['_to'] >= $date)) {
                $currencies[] = $currency;
                $currencyRows[$currency] = ['code' => $currency, 'name' => $money[$currency]['displayName'] ?? $currency,
                    'symbol' => $money[$currency]['symbol'] ?? $currency];
            }
        }
    }
    $spoken = $info[$code]['languagePopulation'] ?? [];
    $official = array_filter($spoken, fn ($data) => isset($data['_officialStatus']));
    $selected = $official ?: $spoken;
    uasort($selected, fn ($a, $b) => (float) ($b['_populationPercent'] ?? 0) <=> (float) ($a['_populationPercent'] ?? 0));
    $countryLanguages = [];
    foreach (array_slice(array_keys($selected), 0, 10) as $language) {
        if ($language === 'und') {
            continue;
        }
        $tag = str_replace('_', '-', $language);
        $countryLanguages[] = $tag;
        $languageRows[$tag] = ['tag' => $tag, 'name_tr' => $languages[$language] ?? $languages[$tag] ?? $tag];
    }
    $countryRows[] = ['code' => $code, 'alpha3' => $country['alpha_3'], 'numeric' => $country['numeric'],
        'name_tr' => $names[$code] ?? $country['name'], 'name_en' => $country['name'], 'emoji' => $country['flag'] ?? null,
        'currencies' => array_values(array_unique($currencies)), 'languages' => $countryLanguages];
}
usort($countryRows, fn ($a, $b) => strcmp($a['code'], $b['code']));
if (count($countryRows) !== 249 || count(array_unique(array_column($countryRows, 'code'))) !== 249) {
    throw new RuntimeException('Upstream ISO snapshot changed; review the country set before rebuilding.');
}
$sources = [];
foreach (glob($input.'/*') as $file) {
    $sources[basename($file)] = hash_file('sha256', $file);
}
$data = ['version' => 'iso-cldr48-2026-09-15', 'as_of' => $date,
    'sources' => ['iso' => 'https://github.com/pycountry/pycountry/tree/main/src/pycountry/databases',
        'cldr' => 'https://github.com/unicode-org/cldr-json/tree/main/cldr-json', 'sha256' => $sources],
    'countries' => $countryRows, 'regions' => $regions,
    'currencies' => array_values($currencyRows), 'languages' => array_values($languageRows)];
$output = $root.'/database/data/global-geo';
if (! is_dir($output)) {
    mkdir($output, 0777, true);
}
file_put_contents($output.'/catalog.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
foreach (['UNICODE-LICENSE.txt', 'ISO-LICENSE.txt'] as $license) {
    copy($input.'/'.$license, $output.'/'.$license);
}
echo count($countryRows).' countries; '.count($regions).' regions; '.count($currencyRows).' currencies; '.count($languageRows)." languages\n";
