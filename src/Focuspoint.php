<?php

declare(strict_types=1);

namespace FriendsOfRedaxo\Focuspoint;

use Redaxo\Core\MediaPool\Media;

use function sprintf;

/**
 * Fokuspunkt eines Bildes in Prozent der Breite (x) und Höhe (y), 0 = links/oben, 100 = rechts/unten.
 *
 * Gespeichert wird er als „x,y" mit einer Nachkommastelle in der Spalte `meta_focuspoint` von `rex_media`
 * (siehe {@see FocuspointMeta}) – dasselbe Format wie `med_focuspoint` der REDAXO-5-Fassung.
 *
 *     Focuspoint::forMedia($media)?->cssPosition();   // "52.3% 35.5%" für object-position
 */
final readonly class Focuspoint
{
    public function __construct(
        public float $x = 50.0,
        public float $y = 50.0,
    ) {}

    /** Liest „x,y"; liefert null bei leerem oder ungültigem Wert. */
    public static function fromString(?string $value): ?self
    {
        if (null === $value || !preg_match('/^\s*(\d{1,3}(?:\.\d+)?)\s*,\s*(\d{1,3}(?:\.\d+)?)\s*$/', $value, $m)) {
            return null;
        }
        $x = (float) $m[1];
        $y = (float) $m[2];
        if ($x > 100 || $y > 100) {
            return null;
        }

        return new self($x, $y);
    }

    /** Fokuspunkt eines Mediums (Objekt oder Dateiname), null wenn keiner gesetzt ist. */
    public static function forMedia(Media|string $media): ?self
    {
        $media = $media instanceof Media ? $media : Media::get($media);
        if (null === $media) {
            return null;
        }

        return self::fromString(FocuspointMeta::of($media)->focuspoint);
    }

    /** Wert für CSS `object-position` / `background-position`. */
    public function cssPosition(): string
    {
        return sprintf('%s%% %s%%', self::number($this->x), self::number($this->y));
    }

    public function __toString(): string
    {
        return self::number($this->x) . ',' . self::number($this->y);
    }

    private static function number(float $value): string
    {
        return number_format($value, 1, '.', '');
    }
}
