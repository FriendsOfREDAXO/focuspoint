<?php

declare(strict_types=1);

namespace FriendsOfRedaxo\Focuspoint\MediaType;

use FriendsOfRedaxo\Focuspoint\Focuspoint;
use FriendsOfRedaxo\Focuspoint\Modifier\FocusCover;
use Redaxo\Core\MediaManager\MediaContext;
use Redaxo\Core\MediaManager\MediaType;
use Redaxo\Core\MediaPool\Media;

/**
 * Basis für Medientypen mit festem Seitenverhältnis, die um den Fokuspunkt zuschneiden ({@see FocusCover}).
 * Name und Maße legt das Projekt per `#[AsMediaType]` an der eigenen Unterklasse fest:
 *
 *     #[AsMediaType('teaser', width: 800, height: 450)]
 *     #[AsMediaType('hero_mobile', width: 800, height: 1000, placeY: 20)]
 *     final readonly class CropTypes extends FocuspointCropType implements NegotiatesFormat
 *     {
 *         use NegotiatesImageFormats;
 *     }
 *
 * `placeX`/`placeY` legen fest, wo im Ausschnitt der Punkt landen soll (Prozent, Standard Mitte).
 * Der Bild-Cache hängt am Quelltext der Unterklasse: Nach einem Update dieses Pakets `console cache:clear`.
 */
abstract readonly class FocuspointCropType implements MediaType
{
    public function __construct(
        public int $width,
        public int $height,
        public float $placeX = 50.0,
        public float $placeY = 50.0,
    ) {}

    public function process(MediaContext $context): void
    {
        $context->image->modify(FocusCover::forFile($context->filename, $this->width, $this->height, $this->placement()));
    }

    /**
     * Lage des Fokuspunkts im ausgelieferten Bild, wenn der Browser es noch einmal beschneidet (`object-position`);
     * null ohne gesetzten Punkt oder ohne bekannte Bildgröße.
     */
    public function focusInResult(Media $media): ?Focuspoint
    {
        $focus = Focuspoint::forMedia($media);
        if (null === $focus || !$media->width || !$media->height) {
            return null;
        }

        return new FocusCover($this->width, $this->height, $focus, $this->placement())->focusInResult($media->width, $media->height);
    }

    private function placement(): Focuspoint
    {
        return new Focuspoint($this->placeX, $this->placeY);
    }
}
