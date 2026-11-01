/*
 * Fokuspunkt auf der Mediendetailseite: Die Vorschau (data-focuspoint-preview, gesetzt von FocuspointAddon) wird zur
 * Klickfläche für das Metafeld (data-focuspoint-input). Ein Klick schreibt „x,y" in Prozent ins Feld; das Feld
 * bleibt per Tastatur bedienbar, die Markierung folgt jeder Eingabe.
 */
(function () {
    'use strict';

    var PATTERN = /^\s*(\d{1,3}(?:\.\d+)?)\s*,\s*(\d{1,3}(?:\.\d+)?)\s*$/;

    function init(root) {
        root.querySelectorAll('[data-focuspoint-preview]').forEach(function (preview) {
            var input = document.querySelector('[data-focuspoint-input]');
            var img = preview.querySelector('img');
            if (preview.dataset.focuspointReady || !input || !img) {
                return;
            }
            preview.dataset.focuspointReady = '1';

            var frame = document.createElement('span');
            frame.className = 'focuspoint-frame';
            img.parentNode.insertBefore(frame, img);
            frame.appendChild(img);

            var marker = document.createElement('span');
            marker.className = 'focuspoint-marker';
            marker.setAttribute('aria-hidden', 'true');
            frame.appendChild(marker);

            var tools = document.createElement('p');
            tools.className = 'focuspoint-tools';
            var hint = document.createElement('span');
            hint.textContent = preview.dataset.focuspointHint || '';
            var reset = document.createElement('button');
            reset.type = 'button';
            reset.className = 'btn btn-default btn-xs';
            reset.textContent = preview.dataset.focuspointReset || 'Reset';
            tools.appendChild(hint);
            tools.appendChild(reset);
            preview.appendChild(tools);

            function show() {
                var m = PATTERN.exec(input.value);
                var valid = m && parseFloat(m[1]) <= 100 && parseFloat(m[2]) <= 100;
                marker.hidden = !valid;
                reset.hidden = '' === input.value.trim();
                if (valid) {
                    marker.style.left = m[1] + '%';
                    marker.style.top = m[2] + '%';
                }
            }

            function set(value) {
                input.value = value;
                input.dispatchEvent(new Event('change', { bubbles: true }));
                show();
            }

            // Die Vorschau steckt in einem Link auf die große Fassung; der Klick setzt hier nur den Punkt.
            frame.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var rect = img.getBoundingClientRect();
                var x = Math.min(100, Math.max(0, (event.clientX - rect.left) / rect.width * 100));
                var y = Math.min(100, Math.max(0, (event.clientY - rect.top) / rect.height * 100));
                set(x.toFixed(1) + ',' + y.toFixed(1));
            });
            reset.addEventListener('click', function () {
                set('');
                input.focus();
            });
            input.addEventListener('input', show);
            show();
        });
    }

    document.addEventListener('rex:ready', function (event) {
        init(event.target instanceof Element ? event.target : document);
    });
    document.addEventListener('DOMContentLoaded', function () {
        init(document);
    });
})();
