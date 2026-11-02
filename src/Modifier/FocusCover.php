<?php

declare(strict_types=1);

namespace FriendsOfRedaxo\Focuspoint\Modifier;

use FriendsOfRedaxo\Focuspoint\Focuspoint;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ModifierInterface;

/**
 * Zuschnitt auf ein festes Seitenverhältnis um den Fokuspunkt herum, danach Verkleinern auf die Zielgröße.
 * Wie `coverDown()` von Intervention, nur dass der Ausschnitt nicht mittig sitzt: Er liegt so, dass der Fokuspunkt
 * möglichst an der Stelle `placement` des Ausschnitts steht (Standard: Mitte), ohne über den Bildrand hinauszugehen.
 * Mit `placement` 50,20 landet der Punkt im oberen Fünftel, etwa wenn unten Text über dem Bild liegt. Vergrößert wird nie.
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
        private Focuspoint $placement = new Focuspoint(),
    ) {}

    /**
     * Mit dem Fokuspunkt des Mediums aus dem Medienpool. Ohne gesetzten Punkt mittig, dann auch ohne `placement`:
     * Die Bildmitte an den oberen Rand zu rücken, würde den oberen Bildteil abschneiden.
     */
    public static function forFile(string $filename, int $width, int $height, Focuspoint $placement = new Focuspoint()): self
    {
        $focus = Focuspoint::forMedia($filename);

        return null === $focus ? new self($width, $height) : new self($width, $height, $focus, $placement);
    }

    public function apply(ImageInterface $image): ImageInterface
    {
        $crop = $this->crop($image->width(), $image->height());
        if (null === $crop) {
            return $image;
        }

        return $image->crop(...$crop)->scaleDown($this->width, $this->height);
    }

    /**
     * Lage des Fokuspunkts im fertigen Bild, für ein weiteres Beschneiden im Browser (`object-position`,
     * `background-position`): Der Ausschnitt verschiebt den Punkt, wenn er nicht mittig liegen kann.
     */
    public function focusInResult(int $sourceWidth, int $sourceHeight): Focuspoint
    {
        $crop = $this->crop($sourceWidth, $sourceHeight);
        if (null === $crop) {
            return $this->focus;
        }
        [$cropWidth, $cropHeight, $x, $y] = $crop;

        return new Focuspoint(
            self::percent($this->focus->x / 100 * $sourceWidth - $x, $cropWidth),
            self::percent($this->focus->y / 100 * $sourceHeight - $y, $cropHeight),
        );
    }

    /** @return array{int, int, int, int}|null Breite, Höhe, linke und obere Kante des Ausschnitts */
    private function crop(int $sourceWidth, int $sourceHeight): ?array
    {
        if ($sourceWidth < 1 || $sourceHeight < 1 || $this->width < 1 || $this->height < 1) {
            return null;
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

        return [
            $cropWidth,
            $cropHeight,
            self::offset($this->focus->x / 100 * $sourceWidth, $cropWidth, $sourceWidth, $this->placement->x),
            self::offset($this->focus->y / 100 * $sourceHeight, $cropHeight, $sourceHeight, $this->placement->y),
        ];
    }

    private static function percent(float $value, int $size): float
    {
        return round(max(0, min(100, $value / $size * 100)), 1);
    }

    /** Linke bzw. obere Kante des Ausschnitts: Fokus an der Stelle `placement` (Prozent), aber innerhalb des Bildes. */
    private static function offset(float $focus, int $crop, int $source, float $placement): int
    {
        return (int) round(max(0, min($focus - $crop * $placement / 100, $source - $crop)));
    }
}
