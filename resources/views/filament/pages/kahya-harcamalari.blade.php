<x-filament-panels::page>
    <div class="space-y-6">
        {{-- LLM kullanımı --}}
        <x-filament::section>
            <x-slot name="heading">Bu ay — sohbet (LLM)</x-slot>
            <x-slot name="description">Token sayıları sağlayıcı faturasıyla karşılaştırılabilir; kesin dolar sağlayıcının panosunda.</x-slot>

            @php $llm = $this->llmSatirlari(); @endphp

            @if ($llm->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Bu ay hiç sohbet çağrısı yok.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                <th class="py-2 pr-4">Model</th>
                                <th class="py-2 pr-4">Çağrı</th>
                                <th class="py-2 pr-4">Girdi token</th>
                                <th class="py-2">Çıktı token</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($llm as $satir)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2 pr-4 font-mono text-xs">{{ $satir->model }}</td>
                                    <td class="py-2 pr-4">{{ number_format($satir->adet) }}</td>
                                    <td class="py-2 pr-4">{{ number_format($satir->girdi) }}</td>
                                    <td class="py-2">{{ number_format($satir->cikti) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        {{-- Dış araçlar --}}
        <x-filament::section>
            <x-slot name="heading">Bu ay — dış gözler</x-slot>
            <x-slot name="description">Limit dolunca ilgili araç durur ve Kâhya sohbette söyler; limitler Kâhya Ayarları'ndan.</x-slot>

            <div class="space-y-6">
                @foreach ($this->aracSatirlari() as $arac)
                    @php
                        $oran = $arac['limit'] > 0 ? min(100, (int) round($arac['kullanim'] * 100 / $arac['limit'])) : 100;
                    @endphp
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $arac['etiket'] }}</span>
                            <span class="{{ $oran >= 90 ? 'text-danger-600 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $arac['kullanim'] }} / {{ $arac['limit'] }}
                            </span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full {{ $oran >= 90 ? 'bg-danger-500' : 'bg-primary-500' }}" style="width: {{ $oran }}%"></div>
                        </div>

                        @if ($arac['sonlar']->isNotEmpty())
                            <ul class="mt-2 space-y-0.5 text-xs text-gray-500 dark:text-gray-400">
                                @foreach ($arac['sonlar'] as $son)
                                    <li>· {{ $son->created_at->format('d.m H:i') }} — {{ $son->detay ?: '(sorgu kaydı yok)' }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
