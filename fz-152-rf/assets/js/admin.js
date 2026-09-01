(function(){
        function copyText(textarea) {
                if (!textarea) {
                        return false;
                }

                textarea.focus();
                textarea.select();

                var ok = false;

                try {
                        ok = document.execCommand('copy');
                } catch (e) {
                        ok = false;
                }

                if (!ok && navigator.clipboard && navigator.clipboard.writeText) {
                        return navigator.clipboard.writeText(textarea.value)
                                .then(function(){ return true; })
                                .catch(function(){ return false; });
                }

                return ok;
        }

        function setStatus(element, message) {
                if (!element) {
                        return;
                }

                element.textContent = message || '';

                if (message) {
                        setTimeout(function(){
                                element.textContent = '';
                        }, 2000);
                }
        }

        function handleClick(event) {
                var button = event.currentTarget;
                var targetId = button.getAttribute('data-f152-copy-target');
                var statusId = button.getAttribute('data-f152-copy-status');
                var successMessage = button.getAttribute('data-f152-copy-success');
                var errorMessage = button.getAttribute('data-f152-copy-error');

                var textarea = targetId ? document.getElementById(targetId) : null;
                var status = statusId ? document.getElementById(statusId) : null;

                var result = copyText(textarea);

                if (result && typeof result.then === 'function') {
                        result.then(function(ok){
                                setStatus(status, ok ? successMessage : errorMessage);
                        });
                } else {
                        setStatus(status, result ? successMessage : errorMessage);
                }
        }

        function init(){
                var buttons = document.querySelectorAll('.f152-copy-btn');
                if (buttons.length) {
                        buttons.forEach(function(button){
                                button.addEventListener('click', handleClick);
                        });
                }

                initColorPickers();
				initPreview();
                initServiceCategoryTabs();
                initSurvey();
                initBrowserScanner();
        }


        function initBrowserScanner() {
                var root = document.querySelector('[data-f152-browser-scan]');
                if (!root || typeof window.F152AdminScanner !== 'object' || !window.F152AdminScanner) {
                        return;
                }

                var cfg = window.F152AdminScanner;
                if (!cfg.homeUrl || !cfg.ajaxUrl || !cfg.nonce || typeof window.fetch !== 'function') {
                        return;
                }

                var status = root.querySelector('[data-f152-browser-scan-status]');
                var chip = root.querySelector('[data-f152-browser-scan-chip]');
                var chipText = root.querySelector('[data-f152-browser-scan-chip-text]');

                function setScanState(state, text) {
                        if (chip) {
                                chip.classList.remove('f152-status-chip--scanning', 'f152-status-chip--success', 'f152-status-chip--error');
                                chip.classList.add('f152-status-chip--' + state);
                        }
                        if (chipText) {
                                chipText.textContent = text;
                        }
                        root.setAttribute('aria-busy', state === 'scanning' ? 'true' : 'false');
                }

                setScanState('scanning', 'Сканирование…');
                if (status) {
                        status.textContent = 'Сканирование ещё выполняется. Результат обновится автоматически.';
                }

                fetch(cfg.homeUrl, {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: { 'X-F152-Browser-Scan': '1' }
                })
                .then(function(response) {
                        if (!response.ok) {
                                throw new Error('HTTP ' + response.status);
                        }
                        return response.text();
                })
                .then(function(html) {
                        if (!html) {
                                throw new Error('empty');
                        }

                        if (html.length > 5 * 1024 * 1024) {
                                html = html.slice(0, 5 * 1024 * 1024);
                        }

                        var body = new FormData();
                        body.append('action', 'f152_browser_scan_home');
                        body.append('nonce', cfg.nonce);
                        body.append('html', html);

                        return fetch(cfg.ajaxUrl, {
                                method: 'POST',
                                credentials: 'same-origin',
                                cache: 'no-store',
                                body: body
                        });
                })
                .then(function(response) {
                        if (!response.ok) {
                                throw new Error('AJAX HTTP ' + response.status);
                        }
                        return response.json();
                })
                .then(function(payload) {
                        if (!payload || !payload.success || !payload.data) {
                                throw new Error('scan failed');
                        }

                        var labels = Array.isArray(payload.data.labels) ? payload.data.labels : [];
                        if (payload.data.changed) {
                                setScanState('scanning', 'Обновляю…');
                                if (status) {
                                        status.textContent = labels.length
                                                ? 'Найдены сервисы: ' + labels.join(', ') + '. Обновляю результаты…'
                                                : 'Результат проверки изменился. Обновляю страницу…';
                                }
                                window.setTimeout(function(){ window.location.reload(); }, 150);
                                return;
                        }

                        setScanState('success', 'Готово');
                        if (status) {
                                status.textContent = labels.length
                                        ? 'Проверка завершена. Браузер дополнительно подтвердил на главной странице: ' + labels.join(', ') + '.'
                                        : 'Проверка завершена. Главная страница дополнительно проверена браузером.';
                        }
                })
                .catch(function(error) {
                        setScanState('error', 'Не завершено');
                        if (status) {
                                status.textContent = 'Проверка браузером не завершилась. Нажмите «Проверить сайт сейчас» или обновите страницу.';
                        }
                        if (window.console && typeof window.console.debug === 'function') {
                                window.console.debug('FZ-152 browser scanner:', error);
                        }
                });
        }


        function initSurvey() {
                var form = document.querySelector('[data-f152-survey]');
                if (!form) {
                        return;
                }

                var roleStep = form.querySelector('[data-f152-survey-step="role"]');
                var partnerStep = form.querySelector('[data-f152-survey-step="partner"]');
                var roleInputs = form.querySelectorAll('input[name="role"]');
                var partnerInputs = form.querySelectorAll('input[name="partner_interest"]');
                var nextButton = form.querySelector('[data-f152-survey-next]');
                var backButton = form.querySelector('[data-f152-survey-back]');
                var ownerSubmit = form.querySelector('[data-f152-survey-owner-submit]');

                if (!roleStep || !partnerStep || !roleInputs.length || !nextButton || !ownerSubmit) {
                        return;
                }

                function getRole() {
                        return form.querySelector('input[name="role"]:checked');
                }

                function setPartnerEnabled(enabled) {
                        partnerInputs.forEach(function(input) {
                                input.disabled = !enabled;
                                input.required = enabled;
                        });
                }

                function updateNextLabel() {
                        var checked = getRole();
                        nextButton.textContent = checked && checked.value === 'owner'
                                ? 'Отправить ответ'
                                : 'Далее';
                }

                roleInputs.forEach(function(input) {
                        input.addEventListener('change', updateNextLabel);
                });

                nextButton.addEventListener('click', function() {
                        var checked = getRole();
                        if (!checked) {
                                roleInputs[0].reportValidity();
                                return;
                        }

                        if (checked.value === 'owner') {
                                setPartnerEnabled(false);
                                if (typeof form.requestSubmit === 'function') {
                                        form.requestSubmit(ownerSubmit);
                                } else {
                                        form.submit();
                                }
                                return;
                        }

                        roleStep.hidden = true;
                        partnerStep.hidden = false;
                        setPartnerEnabled(true);

                        var legend = partnerStep.querySelector('legend');
                        if (legend) {
                                legend.setAttribute('tabindex', '-1');
                                legend.focus();
                        }
                });

                if (backButton) {
                        backButton.addEventListener('click', function() {
                                setPartnerEnabled(false);
                                partnerStep.hidden = true;
                                roleStep.hidden = false;
                                nextButton.focus();
                        });
                }

                setPartnerEnabled(false);
                updateNextLabel();
        }


        function initServiceCategoryTabs() {
                var tabLists = document.querySelectorAll('[data-f152-service-tabs]');
                if (!tabLists.length) {
                        return;
                }

                tabLists.forEach(function(tabList) {
                        var wrap = tabList.closest('.f152-services-wrap');
                        if (!wrap) {
                                return;
                        }

                        var buttons = tabList.querySelectorAll('[data-f152-service-tab]');
                        var cards = wrap.querySelectorAll('[data-f152-service-category]');
                        if (!buttons.length || !cards.length) {
                                return;
                        }

                        var storageKey = 'f152_services_category';
                        var allowed = Array.prototype.map.call(buttons, function(button) {
                                return button.getAttribute('data-f152-service-tab') || '';
                        }).filter(Boolean);
                        var initial = allowed.indexOf('trackers') !== -1 ? 'trackers' : (allowed[0] || 'trackers');

                        try {
                                var queryCategory = new URL(window.location.href).searchParams.get('service_category') || '';
                                if (allowed.indexOf(queryCategory) !== -1) {
                                        initial = queryCategory;
                                } else {
                                        var stored = window.localStorage ? window.localStorage.getItem(storageKey) : '';
                                        if (allowed.indexOf(stored) !== -1) {
                                                initial = stored;
                                        }
                                }
                        } catch (e) {}

                        function activate(category, persist) {
                                if (allowed.indexOf(category) === -1) {
                                        category = allowed.indexOf('trackers') !== -1 ? 'trackers' : (allowed[0] || 'trackers');
                                }

                                buttons.forEach(function(button) {
                                        var active = button.getAttribute('data-f152-service-tab') === category;
                                        button.classList.toggle('nav-tab-active', active);
                                        button.setAttribute('aria-selected', active ? 'true' : 'false');
                                });

                                cards.forEach(function(card) {
                                        card.hidden = card.getAttribute('data-f152-service-category') !== category;
                                });

                                if (persist) {
                                        try {
                                                if (window.localStorage) {
                                                        window.localStorage.setItem(storageKey, category);
                                                }
                                        } catch (e) {}
                                }
                        }

                        buttons.forEach(function(button) {
                                button.addEventListener('click', function() {
                                        activate(button.getAttribute('data-f152-service-tab') || 'trackers', true);
                                });
                        });

                        activate(initial, false);
                });
        }

        function clamp(value, min, max) {
                return Math.min(Math.max(value, min), max);
        }

        function hexToRgb(hex) {
                var normalized = hex.replace('#', '');

                if (normalized.length === 3) {
                        normalized = normalized.split('').map(function(char){ return char + char; }).join('');
                }

                if (normalized.length !== 6) {
                        return null;
                }

                var intVal = parseInt(normalized, 16);

                return {
                        r: (intVal >> 16) & 255,
                        g: (intVal >> 8) & 255,
                        b: intVal & 255
                };
        }

        function rgbToHex(r, g, b) {
                var parts = [r, g, b].map(function(part){
                        var clamped = clamp(parseInt(part, 10) || 0, 0, 255);
                        var hex = clamped.toString(16);
                        return hex.length === 1 ? '0' + hex : hex;
                });

                return '#' + parts.join('');
        }

        function parseColorValue(value, fallback) {
                var color = (value || '').trim();
                var defaultColor = (fallback || '#000000').trim();

                var defaultHexMatch = defaultColor.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
                if (defaultHexMatch) {
                        var base = defaultHexMatch[1];
                        if (base.length === 3) {
                                base = base.split('').map(function(char){ return char + char; }).join('');
                        }
                        defaultColor = '#' + base;
                } else {
                        defaultColor = '#000000';
                }

                if (!color) {
                        color = defaultColor;
                }

                if (color.toLowerCase() === 'transparent') {
                        return { hex: '#000000', alpha: 0 };
                }

                var hexMatch = color.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
                if (hexMatch) {
                        var hexBase = hexMatch[1];
                        if (hexBase.length === 3) {
                                hexBase = hexBase.split('').map(function(char){ return char + char; }).join('');
                        }
                        return { hex: '#' + hexBase, alpha: 1 };
                }

                var rgbaMatch = color.match(/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*(0|0?\.\d+|1(?:\.0+)?))?\s*\)$/i);
                if (rgbaMatch) {
                        var r = clamp(parseInt(rgbaMatch[1], 10) || 0, 0, 255);
                        var g = clamp(parseInt(rgbaMatch[2], 10) || 0, 0, 255);
                        var b = clamp(parseInt(rgbaMatch[3], 10) || 0, 0, 255);
                        var a = rgbaMatch[4] !== undefined ? clamp(parseFloat(rgbaMatch[4]) || 0, 0, 1) : 1;

                        return { hex: rgbToHex(r, g, b), alpha: a };
                }

                return { hex: defaultColor, alpha: 1 };
        }

        function formatColor(hex, alpha) {
                var rgb = hexToRgb(hex);

                if (!rgb) {
                        return hex;
                }

                var normalizedAlpha = clamp(alpha, 0, 1);

                if (normalizedAlpha >= 0.99) {
                        return hex.startsWith('#') ? hex : '#' + hex;
                }

                var alphaStr = normalizedAlpha.toFixed(2).replace(/0+$/,'').replace(/\.$/, '');

                return 'rgba(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ', ' + alphaStr + ')';
        }

        function initColorPickers() {
                var controls = document.querySelectorAll('.f152-color-control');

                if (!controls.length) {
                        return;
                }

                controls.forEach(function(control){
                        var textInput = control.querySelector('.f152-color-input');
                        var button = control.querySelector('.f152-color-button');
                        var picker = control.querySelector('.f152-color-picker');
                        var range = control.querySelector('.f152-opacity__range');
                        var output = control.querySelector('.f152-opacity__value');
                        var fallback = control.getAttribute('data-f152-color-default') || (textInput ? textInput.getAttribute('placeholder') : '') || '#000000';

                        if (!textInput || !button || !picker || !range || !output) {
                                return;
                        }

                        function updateFromText() {
                                var placeholder = textInput.getAttribute('placeholder') || fallback;
                                var parsed = parseColorValue(textInput.value, placeholder);

                                picker.value = parsed.hex;
                                range.value = Math.round(parsed.alpha * 100);
                                output.textContent = range.value + '%';
                        }

                        function applyColor(hex) {
                                var alpha = parseInt(range.value, 10);
                                var normalizedAlpha = isNaN(alpha) ? 100 : clamp(alpha, 0, 100);
                                range.value = normalizedAlpha;
                                output.textContent = normalizedAlpha + '%';

                                var formatted = formatColor(hex, normalizedAlpha / 100);
                                textInput.value = formatted;
                        }

                        button.addEventListener('click', function(){
                                if (typeof picker.showPicker === 'function') {
                                        try {
                                                picker.showPicker();
                                                return;
                                        } catch (e) {
                                        }
                                }
								
                                picker.click();
                        });

                        picker.addEventListener('input', function(){
                                var chosen = picker.value || fallback;
                                applyColor(chosen);
                        });

                        range.addEventListener('input', function(){
                                var colorBase = picker.value || fallback;
                                applyColor(colorBase);
                        });

                        textInput.addEventListener('input', function(){
                                updateFromText();
                        });

                        updateFromText();
                });
        }

        function getFieldValue(id, fallback) {
                var el = document.getElementById(id);
                var value = el && typeof el.value === 'string' ? el.value : '';
                var trimmed = value.trim();

                if (trimmed === '' && typeof fallback === 'string') {
                        return fallback;
                }

                return value;
        }

        function getThemeValue(defaultTheme) {
                var checked = document.querySelector('input[name="f152_theme"]:checked');
                var value = checked && typeof checked.value === 'string' ? checked.value : '';
                return value === 'dark' ? 'dark' : (defaultTheme === 'dark' ? 'dark' : 'light');
        }

        function applyMacros(text, data) {
                var source = (text || '').replace(/\r?\n/g, '<br>');
                var buttons = {
                        '[f152_btn_accept]': '<button type="button" class="f152-btn f152-btn--accept">' + data.labels.accept + '</button>',
                        '[f152_btn_settings]': '<button type="button" class="f152-btn f152-btn--settings">' + data.labels.settings + '</button>',
                        '[f152_btn_reject]': '<button type="button" class="f152-btn f152-btn--reject">' + data.labels.reject + '</button>'
                };

                var macros = {
                        '[f152_site_url]': data.macros.siteUrl,
                        '[f152_company_name]': data.macros.company,
                        '[f152_company_inn]': data.macros.inn,
                        '[f152_company_email]': data.macros.email,
                        '[f152_link_policy_pd]': data.macros.policyPd,
                        '[f152_link_consent_pd]': data.macros.consentPd,
                        '[f152_link_policy_cookie]': data.macros.policyCookie,
                        '[f152_link_consent_marketing]': data.macros.consentMarketing
                };

                Object.keys(buttons).forEach(function(key){
                        source = source.split(key).join(buttons[key]);
                });

                Object.keys(macros).forEach(function(key){
                        source = source.split(key).join(macros[key] || '');
                });

                return source;
        }

        function formatColorFromInput(id) {
                var input = document.getElementById(id);
                var placeholder = input ? input.getAttribute('placeholder') : '';
                var value = input ? input.value : '';
                if (!value || value.trim() === '') {
                        return '';
                }
                var parsed = parseColorValue(value, placeholder);
                return formatColor(parsed.hex, parsed.alpha);
        }

        function applyCssVars(viewport, colors, radius, fontSizes) {
                if (!viewport) return;

                var map = {
                        '--f152-bg': colors.bg,
                        '--f152-text': colors.text,
                        '--f152-link': colors.link,
                        '--f152-btn-bg': colors.btnBg,
                        '--f152-btn-text': colors.btnText,
                        '--f152-btn-accept-bg': colors.btnAcceptBg,
                        '--f152-btn-accept-text': colors.btnAcceptText,
                        '--f152-btn-settings-bg': colors.btnSettingsBg,
                        '--f152-btn-settings-text': colors.btnSettingsText,
                        '--f152-btn-reject-bg': colors.btnRejectBg,
                        '--f152-btn-reject-text': colors.btnRejectText
                };

                Object.keys(map).forEach(function(key){
                        if (map[key]) {
                                viewport.style.setProperty(key, map[key]);
                        } else {
                                viewport.style.removeProperty(key);
                        }
                });

                if (radius) {
                        viewport.style.setProperty('--f152-btn-radius', radius + 'px');
                } else {
                        viewport.style.removeProperty('--f152-btn-radius');
                }

                if (fontSizes) {
                        if (fontSizes.text) {
                                viewport.style.setProperty('--f152-font-size-text', fontSizes.text + 'px');
                        } else {
                                viewport.style.removeProperty('--f152-font-size-text');
                        }
                        if (fontSizes.btn) {
                                viewport.style.setProperty('--f152-font-size-btn', fontSizes.btn + 'px');
                        } else {
                                viewport.style.removeProperty('--f152-font-size-btn');
                        }
                } else {
                        viewport.style.removeProperty('--f152-font-size-text');
                        viewport.style.removeProperty('--f152-font-size-btn');
                }
        }

        function buildPopupHtml(texts, labels, data) {
                var upper = applyMacros(texts.upper, data);
                var func = applyMacros(texts.func, data);
                var anal = applyMacros(texts.anal, data);
                var mark = applyMacros(texts.mark, data);

                return '' +
                        '<div class="f152-popup" role="dialog" aria-hidden="false">' +
                        '  <div class="f152-popup__window" role="document" tabindex="-1">' +
                        '    <button type="button" class="f152-popup__close" aria-label="' + labels.close + '">×</button>' +
                        '    <h3 class="f152-popup__title">Настройка файлов cookie</h3>' +
                        '    <div class="f152-popup__content">' +
                        '      <div class="f152-popup__intro">' + upper + '</div>' +
                        '      <div class="f152-popup__group">' +
                        '        <label class="f152-row">' +
                        '          <input type="checkbox" checked disabled>' +
                        '          <span class="f152-row__title"><strong>Функциональные/технические файлы cookie</strong></span>' +
                        '          <small class="f152-row__desc">' + func + '</small>' +
                        '        </label>' +
                        '      </div>' +
                        '      <div class="f152-popup__group">' +
                        '        <label class="f152-row">' +
                        '          <input type="checkbox" checked>' +
                        '          <span class="f152-row__title"><strong>Аналитические файлы cookie</strong></span>' +
                        '          <small class="f152-row__desc">' + anal + '</small>' +
                        '        </label>' +
                        '      </div>' +
                        '      <div class="f152-popup__group">' +
                        '        <label class="f152-row">' +
                        '          <input type="checkbox" checked>' +
                        '          <span class="f152-row__title"><strong>Рекламные/маркетинговые файлы cookie</strong></span>' +
                        '          <small class="f152-row__desc">' + mark + '</small>' +
                        '        </label>' +
                        '      </div>' +
                        '    </div>' +
                        '    <div class="f152-popup__actions">' +
                        '      <button type="button" class="f152-btn f152-btn--accept">' + labels.accept + '</button>' +
                        '      <button type="button" class="f152-btn f152-btn--reject">' + labels.reject + '</button>' +
                        '      <button type="button" class="f152-btn f152-btn--save">' + labels.save + '</button>' +
                        '    </div>' +
                        '  </div>' +
                        '</div>';
        }

        function initPreview() {
                var preview = document.querySelector('[data-f152-preview]');
                if (!preview) {
                        return;
                }

                var defaults = {
                        banner: preview.getAttribute('data-f152-default-banner') || '',
                        upper: preview.getAttribute('data-f152-default-upper') || '',
                        func: preview.getAttribute('data-f152-default-func') || '',
                        anal: preview.getAttribute('data-f152-default-anal') || '',
                        mark: preview.getAttribute('data-f152-default-mark') || ''
                };

                var labels = {
                        accept: preview.getAttribute('data-f152-label-accept') || 'Принять',
                        settings: preview.getAttribute('data-f152-label-settings') || 'Настроить',
                        reject: preview.getAttribute('data-f152-label-reject') || 'Отклонить',
                        save: preview.getAttribute('data-f152-label-save') || 'Сохранить',
                        close: preview.getAttribute('data-f152-label-close') || 'Закрыть'
                };

                function render() {
                        var data = {
                                macros: {
                                        siteUrl: getFieldValue('f152_site_url', ''),
                                        company: getFieldValue('f152_company_name', ''),
                                        inn: getFieldValue('f152_company_inn', ''),
                                        email: getFieldValue('f152_company_email', ''),
                                        policyPd: getFieldValue('f152_link_policy_pd', ''),
                                        consentPd: getFieldValue('f152_link_consent_pd', ''),
                                        policyCookie: getFieldValue('f152_link_policy_cookie', ''),
                                        consentMarketing: getFieldValue('f152_link_consent_marketing', '')
                                },
                                labels: labels,
                                theme: getThemeValue(preview.getAttribute('data-f152-theme') || 'light'),
                                colors: {
                                        bg: formatColorFromInput('f152_color_bg'),
                                        text: formatColorFromInput('f152_color_text'),
                                        link: formatColorFromInput('f152_color_link'),
                                        btnBg: formatColorFromInput('f152_color_btn_bg'),
                                        btnText: formatColorFromInput('f152_color_btn_text'),
                                        btnAcceptBg: formatColorFromInput('f152_color_btn_accept_bg'),
                                        btnAcceptText: formatColorFromInput('f152_color_btn_accept_text'),
                                        btnSettingsBg: formatColorFromInput('f152_color_btn_settings_bg'),
                                        btnSettingsText: formatColorFromInput('f152_color_btn_settings_text'),
                                        btnRejectBg: formatColorFromInput('f152_color_btn_reject_bg'),
                                        btnRejectText: formatColorFromInput('f152_color_btn_reject_text')
                                },
                                radius: (function(){
                                        var radiusInput = document.getElementById('f152_btn_radius');
                                        var raw = radiusInput && typeof radiusInput.value === 'string' ? radiusInput.value.trim() : '';
                                        return raw === '' ? '' : raw;
                                })(),
                                fontSizes: {
                                        text: (function(){
                                                var el = document.getElementById('f152_font_size_text');
                                                var raw = el && typeof el.value === 'string' ? el.value.trim() : '';
                                                return raw === '' ? '' : raw;
                                        })(),
                                        btn: (function(){
                                                var el = document.getElementById('f152_font_size_btn');
                                                var raw = el && typeof el.value === 'string' ? el.value.trim() : '';
                                                return raw === '' ? '' : raw;
                                        })()
                                }
                        };

                        var bannerText = getFieldValue('f152_banner_text', defaults.banner);

                        var bannerHtml = '<div class="f152-banner" role="dialog"><div class="f152-banner__inner"><div class="f152-banner__text">' + applyMacros(bannerText, data) + '</div></div></div>';

                        var frames = preview.querySelectorAll('[data-f152-preview-frame]');
                        frames.forEach(function(frame){
                                var viewport = frame.querySelector('.f152-preview__viewport');
                                var banner = frame.querySelector('[data-f152-preview-banner]');

										if (!viewport || !banner) {
                                        return;
                                }

                                viewport.setAttribute('data-f152-theme', data.theme);
                                applyCssVars(viewport, data.colors, data.radius, data.fontSizes);

                                banner.innerHTML = bannerHtml;
                        });
                }

                var form = preview.closest('form') || document.querySelector('form[action="options.php"]');
                var inputs = form ? form.querySelectorAll('input, textarea, select') : [];

                inputs.forEach(function(el){
                        el.addEventListener('input', render);
                        el.addEventListener('change', render);
                });

                render();
        }

       function initCustomCssEditor() {
        var textarea = document.getElementById('f152-custom-css');
        if (!textarea) {
        	return;
        }
        if (!window.F152_CODE_EDITOR || !window.wp || !wp.codeEditor || typeof wp.codeEditor.initialize !== 'function') {
        	return;
        }
      
        try {
        	wp.codeEditor.initialize(textarea, window.F152_CODE_EDITOR);
        } catch (e) {

        }
       }
      
       initCustomCssEditor();
      
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
        	init();
        } else {
        	document.addEventListener('DOMContentLoaded', init);
        }
      })();