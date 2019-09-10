<?php

namespace li3_mailer\tests\mocks\net\mail;

class DeliveryWithPath extends \li3_mailer\net\mail\Delivery {

	public static function adaptersPath() {
		return static::$_adapters;
	}

    /**
     * Looks up an adapter by class by name.
     *
     * @see lithium\core\libraries::locate()
     * @param array $config Configuration array of class to be found.
     * @param array $paths Optional array of search paths that will be checked.
     * @return string Returns a fully-namespaced class reference to the adapter class.
     */
    public static function __class($config, $paths = []) {
        return parent::_class($config, $paths);
    }
}

?>