<?php

declare(strict_types=1);

namespace FriendsOfRedaxo\Focuspoint;

use Override;
use Redaxo\Core\Addon\Addon;
use Redaxo\Core\Core;
use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\MediaPool\ExtensionPoint\MediaDetailSidebar;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Asset;

use function in_array;
use function Redaxo\Core\View\escape;

/**
 * Fokuspunkt für Bilder des Medienpools.
 *
 * Das Metafeld bringt {@see FocuspointMeta} mit (Spalte per `console migrate`), den Zuschnitt {@see Modifier\FocusCover}.
 * Hier nur die Bedienung im Backend: Auf der Mediendetailseite wird die Vorschau zur Klickfläche für das Feld.
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

            return '<div class="focuspoint-preview" data-focuspoint-preview data-focuspoint-hint="' . escape(I18n::msg('focuspoint_preview_hint')) . '" data-focuspoint-reset="' . escape(I18n::msg('focuspoint_reset')) . '">' . $ep->subject . '</div>';
        });
    }
}
