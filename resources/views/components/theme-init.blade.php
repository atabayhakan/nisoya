<script>
    // Karanlık/aydınlık tema yönetimi.
    // - İlk yüklemede: localStorage > sistem tercihi > 'light'
    // - Diğer sekmelerle senkronize (storage event)
    // - FOUC (flash of wrong theme) önlemek için <head>'de inline çalıştırılmalı
    (function () {
        const KEY = 'nisoya_theme';

        function getStored() {
            try { return localStorage.getItem(KEY); } catch (e) { return null; }
        }
        function setStored(value) {
            try { localStorage.setItem(KEY, value); } catch (e) {}
        }
        function systemPrefers() {
            return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'dark'
                : 'light';
        }
        function applyTheme(theme) {
            const root = document.documentElement;
            if (theme === 'dark') {
                root.classList.add('dark');
            } else {
                root.classList.remove('dark');
            }
        }

        // İlk yükleme — senkron (FOUC önleme)
        const initial = getStored() || systemPrefers();
        applyTheme(initial);

        // Sistem teması değişirse (kullanıcı override etmediyse güncelle)
        if (window.matchMedia) {
            const mq = window.matchMedia('(prefers-color-scheme: dark)');
            mq.addEventListener('change', (e) => {
                if (!getStored()) {
                    applyTheme(e.matches ? 'dark' : 'light');
                }
            });
        }

        // Diğer sekmelerdeki değişiklikleri senkronla
        window.addEventListener('storage', (e) => {
            if (e.key === KEY) {
                applyTheme(e.newValue || systemPrefers());
            }
        });

        // Global toggle fonksiyonu (header butonu için)
        window.toggleTheme = function () {
            const isDark = document.documentElement.classList.contains('dark');
            const next = isDark ? 'light' : 'dark';
            setStored(next);
            applyTheme(next);
            return next;
        };
    })();
</script>