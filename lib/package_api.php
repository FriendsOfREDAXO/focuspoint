<?php
/**
 *  This file is part of the REDAXO-AddOn "focuspoint".
 *
 *  @author      FriendsOfREDAXO @ GitHub <https://github.com/FriendsOfREDAXO/focuspoint>
 *  @version     4.0.2
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
 *
 */

 class rex_api_focuspoint_package extends rex_api_package {

     /**
      *  Ausführende Funktion des rex_api_call "focuspoint"
      *
      *  prüft die Request-Parameter, initiiert die Bilderstellung und sendet das Bild an die Browser
      */
      public function execute()
      {
          $function = rex_request('function', 'string');
          if (!in_array($function, ['install', 'uninstall', 'activate', 'deactivate', 'delete'])) {
              throw new rex_api_exception('Unknown package function "' . $function . '"!');
          }
          $packageId = rex_request('package', 'string');
          $package = rex_package::get($packageId);
          if ($function == 'uninstall' && !$package->isInstalled()
              || $function == 'activate' && $package->isAvailable()
              || $function == 'deactivate' && !$package->isAvailable()
              || $function == 'delete' && !rex_package::exists($packageId)
          ) {
              return new rex_api_result(true);
          }

          if ($package instanceof rex_null_package) {
              throw new rex_api_exception('Package "' . $packageId . '" doesn\'t exists!');
          }
          $reinstall = 'install' === $function && $package->isInstalled();
          // Die rexstan-Fehlermeldung beruht vermutlich auf Bezügen zu rex_package; ist aber hier nicht lösbar. 
          // @phpstan-ignore-next-line
          $manager = rex_package_manager::factory($package);
          try {
              $package->includeFile('precheck.php', [ 'request' => ($reinstall ? 'reinstall' : $function) ] );
              $message = ''; #$package->getProperty('precheckmsg', '');
              $success = true; # $message == '';
          } catch (rex_functional_exception $e) {
              $message = $e->getMessage();
              $success = false;
          }
          if( $success ) {
              $success = $manager->$function();
              $message = $manager->getMessage();
          }
          $result = new rex_api_result($success, $message);
          if ($success && !$reinstall) {
              $result->setRequiresReboot(true);
          }
          return $result;
      }
      protected function requiresCsrfProtection()
      {
          return false;
      }

 }
