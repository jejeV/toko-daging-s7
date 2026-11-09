<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Tema diterapkan sebelum render agar tidak berkedip --}}
<script>
    (function () {
        var root = document.documentElement;
        var media = window.matchMedia('(prefers-color-scheme: dark)');

        function stored() {
            try { return localStorage.getItem('theme'); } catch (e) { return null; }
        }

        function apply() {
            var t = stored();
            root.classList.toggle('dark', t === 'dark' || (t !== 'light' && media.matches));
        }

        function syncControls() {
            var mode = stored() || 'system';
            document.querySelectorAll('[data-theme-set]').forEach(function (el) {
                el.setAttribute('aria-pressed', el.dataset.themeSet === mode ? 'true' : 'false');
            });
        }

        window.theme = {
            set: function (mode) {
                try {
                    if (mode === 'system') localStorage.removeItem('theme');
                    else localStorage.setItem('theme', mode);
                } catch (e) {}
                apply();
                syncControls();
            },
            toggle: function () {
                this.set(root.classList.contains('dark') ? 'light' : 'dark');
            }
        };

        apply();
        media.addEventListener('change', apply);
        document.addEventListener('DOMContentLoaded', function () {
            syncControls();
            document.addEventListener('click', function (e) {
                var setBtn = e.target.closest('[data-theme-set]');
                if (setBtn) return window.theme.set(setBtn.dataset.themeSet);
                if (e.target.closest('[data-theme-toggle]')) window.theme.toggle();
            });
        });
    })();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

@vite('resources/css/app.css')

{{--
    Prerender halaman saat kursor diam di atas link (Chrome/Edge), jadi pindah menu terasa instan.
    Link yang mengubah state (login cepat, logout) dan halaman di tab baru tidak ikut.
--}}
<script type="speculationrules">
{
    "prerender": [{
        "where": {
            "and": [
                { "href_matches": "/*" },
                { "not": { "href_matches": "/quick-login/*" } },
                { "not": { "href_matches": "/logout" } },
                { "not": { "selector_matches": "[target=_blank], [data-no-prerender]" } }
            ]
        },
        "eagerness": "moderate"
    }]
}
</script>
