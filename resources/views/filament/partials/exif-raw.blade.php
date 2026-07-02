<div class="space-y-2">
    <p class="text-sm text-stone-600 dark:text-stone-400">
        Bu veri yükleme anında orijinal EXIF'ten alındı ve sadece admin tarafından erişilebilir.
        Görselin yayınlanan varyantları (WebP) bu metadata'yı <strong>içermez</strong> — kullanıcıya gösterilmez.
    </p>
    @if (empty($exif))
        <p class="text-sm italic text-stone-500">EXIF metadata boş (dosyada EXIF bilgisi yoktu).</p>
    @else
        <pre class="max-h-96 overflow-auto rounded-lg bg-stone-900 p-4 font-mono text-xs text-emerald-400 dark:bg-stone-950">{{ json_encode($exif, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    @endif
</div>