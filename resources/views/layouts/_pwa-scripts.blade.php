<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register(@json(asset('sw.js')), { scope: @json(rtrim(url('/'), '/') . '/') }).catch(() => {});
        });
    }

    (function () {
        const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        const isIos      = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        const triggers   = () => document.querySelectorAll('[data-pwa-install]');
        let deferredPrompt = null;

        const show = (visible) => triggers().forEach((el) => { el.hidden = !visible; });

        if (standalone) return;

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            deferredPrompt = event;
            show(true);
        });

        window.addEventListener('appinstalled', () => {
            deferredPrompt = null;
            show(false);
        });

        document.addEventListener('DOMContentLoaded', () => {
            if (isIos) show(true);

            triggers().forEach((el) => el.addEventListener('click', async (event) => {
                event.preventDefault();

                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    await deferredPrompt.userChoice;
                    deferredPrompt = null;
                    show(false);
                    return;
                }

                const help = document.getElementById('pwa-ios-help');
                if (help && window.bootstrap) bootstrap.Modal.getOrCreateInstance(help).show();
            }));
        });
    })();
</script>
