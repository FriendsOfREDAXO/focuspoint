<?php

declare(strict_types=1);

namespace FriendsOfRedaxo\Focuspoint\Field;

use Attribute;
use FriendsOfRedaxo\Focuspoint\Focuspoint;
use Override;
use Redaxo\Core\Form\BoundField;
use Redaxo\Core\Form\Field\Text;
use Redaxo\Core\Form\InvalidValueException;
use Redaxo\Core\Form\ValueType;
use Redaxo\Core\Form\Widget\Input;
use Redaxo\Core\Translation\Translatable;
use Redaxo\Core\Util\Type;
use Redaxo\Core\View\HtmlAttributes;

/**
 * Textfeld für den Fokuspunkt („x,y" in Prozent). `assets/focuspoint.js` verbindet es mit dem Vorschaubild der
 * Mediendetailseite: ein Klick ins Bild schreibt die Koordinaten hinein. Das Feld bleibt per Tastatur bedienbar.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class FocuspointPicker extends Text
{
    #[Override]
    public function fromRaw(string|array|null $raw, ValueType $type): ?string
    {
        $value = parent::fromRaw($raw, $type);
        if (null === $value || '' === trim($value)) {
            return null;
        }

        $point = Focuspoint::fromString($value);
        if (null === $point) {
            throw new InvalidValueException(new Translatable('focuspoint_invalid'));
        }

        return (string) $point;
    }

    #[Override]
    public function widget(BoundField $field): Input
    {
        return new Input(
            $field->inputName,
            Type::string($field->raw),
            $field->id,
            'text',
            $this->required,
            attributes: new HtmlAttributes([
                'data-focuspoint-input' => true,
                'inputmode' => 'decimal',
                'placeholder' => '50.0,50.0',
                'maxlength' => 11,
            ]),
        );
    }
}
