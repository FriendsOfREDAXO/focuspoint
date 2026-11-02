<?php

declare(strict_types=1);

namespace FriendsOfRedaxo\Focuspoint;

use Override;
use Redaxo\Core\Addon\Addon;
use Redaxo\Core\Core;
use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\MediaManager\MediaManager;
use Redaxo\Core\MediaPool\ExtensionPoint\MediaDetailSidebar;
use Redaxo\Core\MediaPool\Media;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Asset;
use Redaxo\Core\View\Component\Section;
use Redaxo\Core\View\Html;
use Redaxo\Core\View\HtmlAttributes;

use function in_array;
use function Redaxo\Core\View\escape;

/**
 * Fokuspunkt für Bilder des Medienpools.
 *
 * Das Metafeld bringt {@see FocuspointMeta} mit (Spalte per `console migrate`), den Zuschnitt {@see Modifier\FocusCover}.
 * Hier nur die Bedienung im Backend: Auf der Mediendetailseite ersetzt die Fokuspunkt-Auswahl die Vorschau, wie in der
 * REDAXO-5-Fassung (Fadenkreuz auf dem Bild, Koordinaten, Zurücksetzen, Vergrößern).
 */
final class FocuspointAddon extends Addon
{
    /** Formate, die sich zuschneiden lassen; SVG und PDF bekommen keine Klickfläche. */
    private const array IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'tif', 'tiff', 'heic'];

    #[Override]
    public function boot(): void
    {
        if (!Core::isBackend()) {
            return;
        }

        // Auf jeder Backend-Seite: Der Medienpool öffnet sich auch als Overlay (Medienfeld, Struktur), und das Overlay
        // übernimmt nur den Hauptinhalt der geladenen Seite, nicht deren Skripte.
        Asset::addCssFile($this->getAssetsUrl('focuspoint.css'));
        Asset::addJsFile($this->getAssetsUrl('focuspoint.js'));

        Extension::register(MediaDetailSidebar::class, static function (MediaDetailSidebar $ep): ?string {
            if (!in_array($ep->media->getExtension(), self::IMAGE_EXTENSIONS, true) || '' === trim($ep->subject)) {
                return null;
            }

            return (string) new Section(
                title: I18n::msg('focuspoint_panel'),
                content: Html::raw(self::panel($ep->media)),
                attributes: new HtmlAttributes(['class' => ['focuspoint-panel'], 'data-focuspoint-panel' => true, 'data-focuspoint-select' => I18n::msg('focuspoint_panel_select')]),
            )->render();
        });
    }

    /** Inhalt der Fokuspunkt-Auswahl; `assets/focuspoint.js` verbindet sie mit dem Metafeld. */
    private static function panel(Media $media): string
    {
        $msg = static fn (string $key): string => escape(I18n::msg($key));

        return '
            <div class="focuspoint-panel-image" data-focuspoint-image tabindex="0" role="application" aria-label="' . $msg('focuspoint_panel_image') . '">
                <img src="' . escape(MediaManager::getUrl('rex_media_medium', $media)) . '" alt="' . escape($media->title) . '">
                <span class="focuspoint-panel-pointer" data-focuspoint-pointer aria-hidden="true"></span>
            </div>
            <p class="focuspoint-panel-info"><span data-focuspoint-info aria-live="polite">50.0% | 50.0%</span></p>
            <div class="focuspoint-panel-toolbar rex-button-toolbar">
                <div class="rex-dropdown">
                    <button class="rex-button" data-variant="primary" data-size="sm" type="button" data-rex-dropdown aria-expanded="false">' . $msg('focuspoint_panel_reset') . ' <span class="rex-caret" aria-hidden="true"></span></button>
                    <ul class="rex-dropdown-menu">
                        <li><a href="#" data-focuspoint-action="initial">' . $msg('focuspoint_panel_initial') . '</a></li>
                        <li><a href="#" data-focuspoint-action="remove">' . $msg('focuspoint_panel_remove') . '</a></li>
                    </ul>
                </div>
                <button class="rex-button" data-variant="primary" data-size="sm" type="button" data-focuspoint-zoom aria-pressed="false" aria-label="' . $msg('focuspoint_panel_zoom') . '"><i class="rex-icon fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i><i class="rex-icon fa-solid fa-magnifying-glass-minus" aria-hidden="true"></i></button>
            </div>';
    }
}
