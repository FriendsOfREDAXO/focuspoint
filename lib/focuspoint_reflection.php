<?php
/**
 *  This file is part of the REDAXO-AddOn "focuspoint".
 *
 *  @author      FriendsOfREDAXO @ GitHub <https://github.com/FriendsOfREDAXO/focuspoint>
 *  @version     2.2.0
 *  @copyright   FriendsOfREDAXO <https://friendsofredaxo.github.io/>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 *  ------------------------------------------------------------------------------------------------
 *
 *  focuspoint_reflection ist eine erweiterte PHP ReflectionClass. Sie erlaubt auf etwas
 *  vereinfachte Art Properties abzufragen oder zu ändern bzw. Methoden auszuführen, die
 *  in der Klasse ansonsten nicht zugänglich sind (private oder protected)
 *
 *  HANDLE WITH CARE!
 *
 *  @method void function executeMethod ($method, array $params)
 *  @method void function getPropertyValue ( $prop )
 *  @method void function setPropertyValue ( $prop, $value )
 */

class focuspoint_reflection extends ReflectionClass {

    public $obj = null;

    function __construct( $obj ) {
        parent::__construct( $obj );
        $this->obj = $obj;
    }

    function executeMethod ($method, array $params){
        $method = $this->getMethod( $method );
        $method->setAccessible(true);
        return $method->invokeArgs($this->obj, $params);
    }

    function getPropertyValue ( $prop ) {
        $property = $this->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue( $this->obj );
    }

    function setPropertyValue ( $prop, $value ) {
        $property = $this->getProperty($prop);
        $property->setAccessible(true);
        $property->setValue( $this->obj, $value );
    }
}
