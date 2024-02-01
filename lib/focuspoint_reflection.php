<?php
/**
 *  This file is part of the REDAXO-AddOn "focuspoint".
 *
 *  @author      FriendsOfREDAXO @ GitHub <https://github.com/FriendsOfREDAXO/focuspoint>
 *  @version     4.1.0
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
 */

namespace FriendsOfRedaxo\Focuspoint;

use ReflectionClass;

class focuspoint_reflection extends ReflectionClass
{
    /** 
     * @var object 
     * @api
     */
    public $obj;

    /**
     *  @return void
     */
    public function __construct(object $obj)
    {
        parent::__construct($obj);
        $this->obj = $obj;
    }

    /**
     *  @api
     *  @param string $method
     *  @param array<mixed> $params
     *  @return mixed
     */
    public function executeMethod($method, array $params)
    {
        $method = $this->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($this->obj, $params);
    }

    /**
     *  @api
     *  @param string $prop
     *  @return mixed
     */
    public function getPropertyValue($prop)
    {
        $property = $this->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue($this->obj);
    }

    /**
     *  @api
     *  @param string $prop
     *  @param mixed $value
     *  @return void
     */
    public function setPropertyValue($prop, $value)
    {
        $property = $this->getProperty($prop);
        $property->setAccessible(true);
        $property->setValue($this->obj, $value);
    }
}
