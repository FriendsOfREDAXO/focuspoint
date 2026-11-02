/*
 * Fokuspunkt-Auswahl auf der Mediendetailseite (data-focuspoint-panel, gesetzt von FocuspointAddon), aufgebaut wie in
 * der REDAXO-5-Fassung: Das Fadenkreuz zeigt beim Öffnen den gespeicherten Punkt, ohne Wert die Bildmitte. Ein Klick
 * ins Bild (oder die Pfeiltasten) schreibt „x,y" in Prozent ins Metafeld (data-focuspoint-input), die Leiste darunter
 * zeigt die Koordinaten unter der Maus. „Zurücksetzen" stellt den Ausgangswert her oder leert das Feld, die Lupe
 * verbreitert die Spalte.
 */
(function () {
    'use strict';

    var PATTERN = /^\s*(\d{1,3}(?:\.\d+)?)\s*,\s*(\d{1,3}(?:\.\d+)?)\s*$/;

    function parse(value) {
        var m = PATTERN.exec(value || '');
        if (!m || parseFloat(m[1]) > 100 || parseFloat(m[2]) > 100) {
            return null;
        }
        return { x: parseFloat(m[1]), y: parseFloat(m[2]) };
    }

    function clamp(value) {
        return Math.min(100, Math.max(0, value));
    }

    function init(root) {
        root.querySelectorAll('[data-focuspoint-panel]').forEach(function (panel) {
            // Feld aus derselben Ansicht: Über der Mediendetailseite kann ein Overlay mit einer zweiten liegen
            var scope = panel.closest('dialog') || document;
            var input = scope.querySelector('[data-focuspoint-input]');
            var image = panel.querySelector('[data-focuspoint-image]');
            if (panel.dataset.focuspointReady || !input || !image) {
                return;
            }
            panel.dataset.focuspointReady = '1';

            var pointer = panel.querySelector('[data-focuspoint-pointer]');
            var info = panel.querySelector('[data-focuspoint-info]');
            var zoom = panel.querySelector('[data-focuspoint-zoom]');
            var initial = input.value;
            var current = parse(initial) || { x: 50, y: 50 };

            function text(pos) {
                return pos.x.toFixed(1) + '% | ' + pos.y.toFixed(1) + '%';
            }

            function show() {
                pointer.style.left = current.x + '%';
                pointer.style.top = current.y + '%';
                info.textContent = text(current);
            }

            function set(pos, value) {
                current = pos;
                input.value = undefined === value ? pos.x.toFixed(1) + ',' + pos.y.toFixed(1) : value;
                input.dispatchEvent(new Event('change', { bubbles: true }));
                show();
            }

            function fromEvent(event) {
                var rect = image.getBoundingClientRect();
                return {
                    x: clamp((event.clientX - rect.left) / rect.width * 100),
                    y: clamp((event.clientY - rect.top) / rect.height * 100),
                };
            }

            image.addEventListener('click', function (event) {
                event.preventDefault();
                set(fromEvent(event));
            });
            image.addEventListener('mousemove', function (event) {
                info.textContent = text(fromEvent(event));
            });
            image.addEventListener('mouseleave', show);
            image.addEventListener('keydown', function (event) {
                var step = event.shiftKey ? 10 : 1;
                var moves = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step] };
                if (!moves[event.key]) {
                    return;
                }
                event.preventDefault();
                set({ x: clamp(current.x + moves[event.key][0]), y: clamp(current.y + moves[event.key][1]) });
            });

            panel.querySelectorAll('[data-focuspoint-action]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    if ('initial' === link.dataset.focuspointAction) {
                        set(parse(initial) || { x: 50, y: 50 }, initial);
                    } else {
                        set({ x: 50, y: 50 }, '');
                    }
                });
            });

            var column = panel.closest('[data-span]');
            var span = column ? column.dataset.span : '';
            if (zoom && column) {
                zoom.addEventListener('click', function () {
                    var wide = 'true' !== zoom.getAttribute('aria-pressed');
                    zoom.setAttribute('aria-pressed', String(wide));
                    column.dataset.span = wide ? 'sm:12' : span;
                });
            } else if (zoom) {
                zoom.hidden = true;
            }

            input.addEventListener('input', function () {
                current = parse(input.value) || { x: 50, y: 50 };
                show();
            });

            // Fadenkreuz-Taste vor dem Feld wie in der REDAXO-5-Fassung: führt zur Auswahl
            if (!input.closest('.rex-input-group')) {
                var group = document.createElement('div');
                group.className = 'rex-input-group';
                var actions = document.createElement('span');
                actions.className = 'rex-input-group-actions';
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'rex-button';
                button.dataset.variant = 'field';
                button.setAttribute('aria-label', panel.dataset.focuspointSelect || '');
                button.innerHTML = '<i class="rex-icon fa-solid fa-crosshairs" aria-hidden="true"></i>';
                button.addEventListener('click', function () {
                    panel.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    image.focus({ preventScroll: true });
                });
                input.parentNode.insertBefore(group, input);
                actions.appendChild(button);
                group.appendChild(actions);
                group.appendChild(input);
            }

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
