<?php

namespace li3_mailer\tests\cases\net\mail;

use li3_mailer\tests\mocks\net\mail\DeliveryWithPath;
use li3_mailer\net\mail\Transport;

class DeliveryTest extends \lithium\test\Unit {

	public function testUsesGoodAdapters() {
		$class = DeliveryWithPath::__class(array('adapter' => 'Simple'), DeliveryWithPath::adaptersPath());
		$adapter = new $class();
		$this->assertTrue($adapter instanceof Transport);
	}
}

?>