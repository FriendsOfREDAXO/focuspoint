<?php

declare(strict_types=1);

namespace FriendsOfRedaxo\Focuspoint\Modifier;

use FriendsOfRedaxo\Focuspoint\Focuspoint;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ModifierInterface;

/**
 * Zuschnitt auf ein festes Seitenverhältnis um den Fokuspunkt herum, danach Verkleinern auf die Zielgröße.
 * Wie `coverDown()` von Intervention, nur dass der Ausschnitt nicht mittig sitzt: Er liegt so, dass der Fokuspunkt
 * möglichst in seiner Mitte steht, ohne über den Bildrand hinauszugehen. Vergrößert wird nie.
 *
 * In einem Medientyp:
 *
 *     #[AsMediaType('teaser')]
 *     final class Teaser implements MediaType
 *     {
 *         public function process(MediaContext $context): void
 *         {
 *             $context->image->modify(FocusCover::forFile($context->filename, 800, 450));
 *         }
 *     }
 */
final readonly class FocusCover implements ModifierInterface
{
    public function __construct(
        private int $width,
        private int $height,
        private Focuspoint $focus = new Focuspoint(),
    ) {}

    /** Mit dem Fokuspunkt des Mediums aus dem Medienpool, ohne gesetzten Punkt mittig. */
    public static function forFile(string $filename, int $width, int $height): self
    {
        return new self($width, $height, Focuspoint::forMedia($filename) ?? new Focuspoint());
    }

    public function apply(ImageInterface $image): ImageInterface
    {
        $sourceWidth = $image->width();
        $sourceHeight = $image->height();
        if ($sourceWidth < 1 || $sourceHeight < 1 || $this->width < 1 || $this->height < 1) {
            return $image;
        }

        // Größter Ausschnitt im Zielverhältnis
        $ratio = $this->width / $this->height;
        if ($sourceWidth / $sourceHeight > $ratio) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($sourceHeight * $ratio);
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / $ratio);
        }
        $cropWidth = max(1, min($cropWidth, $sourceWidth));
        $cropHeight = max(1, min($cropHeight, $sourceHeight));

        $x = self::offset($this->focus->x / 100 * $sourceWidth, $cropWidth, $sourceWidth);
        $y = self::offset($this->focus->y / 100 * $sourceHeight, $cropHeight, $sourceHeight);

        return $image->crop($cropWidth, $cropHeight, $x, $y)->scaleDown($this->width, $this->height);
    }

    /** Linke bzw. obere Kante des Ausschnitts: Fokus in der Mitte, aber innerhalb des Bildes. */
    private static function offset(float $focus, int $crop, int $source): int
    {
        return (int) round(max(0, min($focus - $crop / 2, $source - $crop)));
    }
}
