{{-- Seçilen ülkeye göre şehir önerileri (serbest yazım da mümkün). #country_code select'ine bağlıdır. --}}
<datalist id="city-options"></datalist>
<script>
    (function () {
        const data = @json($citiesByCountry ?? [], JSON_UNESCAPED_UNICODE);
        const countrySel = document.getElementById('country_code');
        const list = document.getElementById('city-options');
        if (!countrySel || !list) return;

        function fill() {
            const cities = data[countrySel.value] || [];
            list.innerHTML = '';
            cities.forEach((name) => {
                const opt = document.createElement('option');
                opt.value = name;
                list.appendChild(opt);
            });
        }

        countrySel.addEventListener('change', fill);
        fill();
    })();
</script>
