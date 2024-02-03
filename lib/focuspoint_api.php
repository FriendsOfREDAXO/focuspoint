<?php
/**
 *  This file is part of the REDAXO-AddOn "focuspoint".
 *
 *  @author      FriendsOfREDAXO @ GitHub <https://github.com/FriendsOfREDAXO/focuspoint>
 *  @version     4.2.0
 *  @copyright   FriendsOfREDAXO <https://friendsofredaxo.github.io/>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 *  ------------------------------------------------------------------------------------------------
 *
 *  Liefert per API-Call ein vom media-manager bearbeitetes Bild für die Preview-Funktion
 *
 *  Das Problem: die Focuspoint-Parameter sind nur temporär.
 *
 *  Da es im media-manager  aufgrund des Caching keinen Weg gibt, alternative Focuspoint-Parameter
 *  per URL unterzuschieben, wird mit diesem API ein eigener Weg eröffnet, Images zu erzeugen.
 *
 *  Das Verfahren arbeitet grob so:
 *
 *  1) lösche Cache-Dateien (damit das Bild auch wirklich neu gerechnet wird)
 *  2) Berechne das Bild neu (auf Basis der Werte im Parameter XY)
 *  3) lösche Cache-Dateien erneut (sind ja nur temporär gedacht)
 *  4) Schicke das Bild zum Client.
 *
 *  Das Bild wird abgerufen mit
 *
 *      index.php?page=structure&rex-api-call=focuspoint
 *               &file=      Name der Mediendatei
 *               &type=      Name des MM-Effektes
 *               &xy=        Fokuspunkt numerisch (0.0,0.0 bis 100.1,100.0)
 */

class rex_api_focuspoint extends rex_api_function
{
    //	protected $published = true;

    /**
     *  Ausführende Funktion des rex_api_call "focuspoint".
     *
     *  prüft die Request-Parameter, initiiert die Bilderstellung und sendet das Bild an die Browser
     */
    public function execute()
    {

        $mediafile = rex_request('file', 'string', '');
        $mediatype = rex_request('type', 'string', '');

        if ('' < $mediafile && '' < $mediatype) {
            $bild = FocuspointMediaManager::createMedia($mediatype, $mediafile);
            $bild->sendMedia('', '');
        }
        exit;
    }
}

/**
 *  Da die wichtige Funktion rex_media_manager->applyEffects 'protected' ist, muss eine abgeleitete
 *  Klasse "focuspoint_media_manager" zwischengeschaltet werden, um das neue Bild zu generieren.
 */
class FocuspointMediaManager extends rex_media_manager
{
    /**
     *  Erzeugt das Bild des media-Effektes.
     *
     *  @param  string $type        Medientyp
     *  @param  string $file        Name der Bilddatei im Medienpool
     *
     *  @return rex_managed_media   Ergebnisbild
     */
    public static function createMedia($type = null, $file = null)
    {
        $mediaPath = rex_path::media($file);
        $media = new rex_managed_media($mediaPath);
        $manager = new self($media);
        $manager->setCachePath('');
        $manager->applyEffects($type);
        $media->refreshImageDimensions();
        return $media;
    }
}
