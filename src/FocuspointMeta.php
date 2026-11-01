<?php

declare(strict_types=1);

namespace FriendsOfRedaxo\Focuspoint;

use FriendsOfRedaxo\Focuspoint\Field\FocuspointPicker;
use Redaxo\Core\MetaInfo\AsMetaSchema;
use Redaxo\Core\MetaInfo\MetaEntity;
use Redaxo\Core\MetaInfo\MetaSchema;
use Redaxo\Core\Translation\Translatable;

/**
 * Metafeld „Fokuspunkt" der Medien, Spalte `meta_focuspoint` in `rex_media` (legt `console migrate` an).
 * Gilt für alle Sprachen gemeinsam. Lesen über {@see Focuspoint::forMedia()}.
 */
#[AsMetaSchema(MetaEntity::Media)]
final class FocuspointMeta extends MetaSchema
{
    #[FocuspointPicker(new Translatable('focuspoint_label'), note: new Translatable('focuspoint_notice'), maxLength: 20)]
    public ?string $focuspoint = null;
}
