{{-- Honeypot koruması için gizli alan. POST formlarında @include('partials.honeypot') ile eklenir.
     App\Http\Middleware\HoneypotMiddleware tarafından kontrol edilir. --}}
<input type="text" name="website" value="" tabindex="-1" autocomplete="off"
       class="hidden" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">