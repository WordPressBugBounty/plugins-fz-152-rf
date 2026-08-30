(function(){
        if (!window.F152 || !F152.metrika || !F152.metrika.id) {
                return;
        }

        var id = String(F152.metrika.id);
        if (!id) {
                return;
        }

        var cookieCfg = F152.cookie || {};
        var currentVersion = Object.prototype.hasOwnProperty.call(cookieCfg, 'version') ? String(cookieCfg.version) : '';
        var legacyVersion = Object.prototype.hasOwnProperty.call(cookieCfg, 'legacyVersion') ? String(cookieCfg.legacyVersion) : currentVersion;
        var mode = (F152.metrika && F152.metrika.mode) || 'always';

        function readCookie(name) {
                var pattern = new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()\[\]\\/+^])/g, '\\$1') + '=([^;]*)');
                var match = document.cookie.match(pattern);
                return match ? decodeURIComponent(match[1]) : null;
        }

        function parseConsent() {
                try {
                        var raw = readCookie(cookieCfg.name || 'f152_consent');
                        if (!raw) {
                                return null;
                        }

                        var obj = JSON.parse(raw);
                        return obj && typeof obj === 'object' ? obj : null;
                } catch (e) {
                        return null;
                }
        }

        function isConsentCurrent(consent) {
                if (!consent || typeof consent !== 'object') {
                        return false;
                }

                if (Object.prototype.hasOwnProperty.call(consent, 'v')) {
                        return String(consent.v) === currentVersion;
                }

                return currentVersion === legacyVersion;
        }

        function analyticsAllowed(consent) {
                consent = consent || parseConsent();
                if (!consent) {
                        return true;
                }

                if (Object.prototype.hasOwnProperty.call(consent, 'analytics') && consent.analytics === false) {
                        return false;
                }

                return true;
        }

        function shouldStart(consent) {
                if (mode === 'always') {
                        return true;
                }

                if (mode === 'require_accept') {
                        return !!(consent && isConsentCurrent(consent) && analyticsAllowed(consent));
                }

                if (mode === 'disable_on_reject') {
                        return analyticsAllowed(consent);
                }

                return true;
        }

        function isStarted() {
                return !!(
                        window.__F152_METRIKA_STARTED__ &&
                        window.__F152_METRIKA_STARTED__[id]
                );
        }

        function markStarted() {
                window.__F152_METRIKA_STARTED__ = window.__F152_METRIKA_STARTED__ || {};
                window.__F152_METRIKA_STARTED__[id] = true;
        }

        function startMetrika() {
                if (isStarted()) {
                        return;
                }

                var consent = parseConsent();
                if (!shouldStart(consent)) {
                        return;
                }

                var scriptUrl = 'https://mc.yandex.ru/metrika/tag.js?id=' + id;

                (function(m,e,t,rurl,i,k,a){
                        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
                        m[i].l=1*new Date();

                        var scr = e.getElementsByTagName('script');
                        for (var j = 0; j < scr.length; j++) {
                                if (scr[j].src === rurl) {
                                        break;
                                }
                        }
                        if (j === scr.length) {
                                k=e.createElement(t);
                                a=e.getElementsByTagName(t)[0];
                                k.async=1;
                                k.src=rurl;
                                a.parentNode.insertBefore(k,a);
                        }
                })(window, document, 'script', scriptUrl, 'ym');

                try {
                        window.ym(parseInt(id, 10), 'init', {
                                ssr: true,
                                webvisor: true,
                                clickmap: true,
                                ecommerce: 'dataLayer',
                                accurateTrackBounce: true,
                                trackLinks: true
                        });
                        markStarted();
                } catch (e) {}
        }

        document.addEventListener('f152:consent-changed', function() {
                startMetrika();
        });

        startMetrika();
})();
