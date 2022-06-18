<?php
/**
 *  This file is part of the REDAXO-AddOn ".....".
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
 *  Perform extended cross-checks upfront to management-actions for addons called from
 *  page=packages. management-actions are
 *      - install         with activate
 *      - reinstall       technically a re-install is identical to install
 *      - uninstall       with deactivate
 *      - activate
 *      - deactivate
 *      - delete          delete is uninstall plus removing all files
 *
 *  this script is called upfront to the management-action in question to perform extended
 *  integration checks not coverable by standard dependency-rules from package.yml
 *
 *  Example:
 *      an addon provides an effect for the media-manager-addon. If the effect is actively
 *      used be a media-manager-type, this addon cannot be removed or deactivated without risking a
 *      fatal error in cas the emdia-manager (re-)builds an image.
 *
 *      precheck.php is intended to check espacially these backward-dependency and prevent the
 *      management-action before touching the system.
 *
 *  Input:
 *      As checks can be different for different managemant actions, the requested action is
 *      provided in the variable $request
 *
 *  Output - checks passed:
 *      just end the script
 *
 *  Output - checks NOT passed:
 *      just end the script, but set an installmsg
 *          $this->setProperty('installmsg', '«detailed error message»' );
 *      or throw a functional exception
 *          throw new rex_functional_exception('«detailed error message»');
 *
 *  Note:
 *      in case of an install (not re-install) or update the addon addon/lib and other configurations
 *      are not loaded. Be carefull. But generally special prechecks are not necessary for "install"
 *
 *  @var rex_addon $this
 *  @var string $request
 */

$message = '';
$header = '';
switch ( $request )
{
    case 'install':
        //noop
        break;
    case 'reinstall':
        //noop
        break;
    case 'activate':
        $message = focuspoint::checkActivateDependencies();
        $header = 'addon_no_activation';
        break;
    case 'deactivate':
        $message = focuspoint::checkDeactivateDependencies();
        $header = 'addon_no_deactivation';
        break;
    case 'uninstall':
        $message = focuspoint::checkUninstallDependencies();
        $header = 'addon_no_uninstall';
        break;
    case 'delete':
        $message = focuspoint::checkUninstallDependencies();
        $header = 'addon_not_deleted';
        break;
}
dump($message);
if( $message ){
    $message = rex_i18n::rawMsg($header, $this->getName()) . "<br>$message";
    throw new rex_functional_exception( $message );
}
