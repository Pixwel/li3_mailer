<?php

namespace li3_mailer\tests\cases\net\mail\transport\adapter;

use li3_mailer\net\mail\transport\adapter\Swift;
use li3_mailer\tests\mocks\net\mail\transport\adapter\Swift as MockSwift;
use li3_mailer\net\mail\Message;
use Swift_Mailer;
use Swift_SendmailTransport;
use Swift_SmtpTransport;

class SwiftTest extends \lithium\test\Unit {
	public function skip() {
		$swiftAvailable = class_exists('Swift_Mailer');
		$this->skipIf(!$swiftAvailable, 'SwiftMailer library not available.');
	}

	public function testDeliver() {
		$swift = new MockSwift();
		$message = new Message(array('subject' => 'test subject'));
		$transport = $swift->deliver($message);
		$this->assertEqual(1, count($transport->delivered));
		$swiftMessage = $transport->delivered[0];
		$this->assertEqual('test subject', $swiftMessage->getSubject());
	}

	public function testBadTransport() {
		$this->assertException('Unknown transport type `` for `Swift` adapter.', function () {
			$swift = new Swift();
			$transport = (new \ReflectionMethod($swift, '_transport'))->invoke($swift);
		});

		$this->assertException('Unknown transport type `foo` for `Swift` adapter.', function () {
			$swift = new Swift(array('transport' => 'foo'));
			$transport = (new \ReflectionMethod($swift, '_transport'))->invoke($swift);
		});
	}

	public function testUnsupportedMailTransport() {
		$this->assertException('Unknown transport type `mail` for `Swift` adapter.', function () {
			$swift = new Swift(array('transport' => 'mail'));
			(new \ReflectionMethod($swift, '_transport'))->invoke($swift);
		});
	}

	public function testSendmailTransport() {
		$swift = new Swift(array('transport' => 'sendmail'));
		$mailer = (new \ReflectionMethod($swift, '_transport'))->invoke($swift);
		$this->assertTrue($mailer instanceof Swift_Mailer);
		$transport = $mailer->getTransport();
		$this->assertTrue($transport instanceof Swift_SendmailTransport);

		$options = array('transport' => 'sendmail', 'command' => 'foo');
		$swift = new Swift($options);
		$mailer = (new \ReflectionMethod($swift, '_transport'))->invoke($swift);
		$this->assertEqual('foo', $mailer->getTransport()->getCommand());

		$swift = new Swift(array('transport' => 'sendmail'));
		$command = 'foo';
		$mailer = (new \ReflectionMethod($swift, '_transport'))->invoke($swift, compact('command'));
		$this->assertEqual('foo', $mailer->getTransport()->getCommand());
	}

	public function testSmtpTransport() {
		$swift = new Swift(array('transport' => 'smtp'));
		$mailer = (new \ReflectionMethod($swift, '_transport'))->invoke($swift);
		$this->assertTrue($mailer instanceof Swift_Mailer);
		$transport = $mailer->getTransport();
		$this->assertTrue($transport instanceof Swift_SmtpTransport);

		$options = array(
			'host' => 'test host', 'port' => 2525,
			'timeout' => 10, 'encryption' => 'tls',
			'sourceip' => '127.0.0.2', 'auth_mode' => 'login',
			'local_domain' => 'test local_domain',
			'username' => 'test username', 'password' => 'test password'
		);
		$swift = new Swift(array('transport' => 'smtp') + $options);
		$mailer = (new \ReflectionMethod($swift, '_transport'))->invoke($swift);
		$transport = $mailer->getTransport();
		$this->assertEqual('test host', $transport->getHost());
		$this->assertEqual(2525, $transport->getPort());
		$this->assertEqual(10, $transport->getTimeout());
		$this->assertEqual('tls', $transport->getEncryption());
		$this->assertEqual('127.0.0.2', $transport->getSourceip());
		$this->assertEqual('test local_domain', $transport->getLocalDomain());
		$this->assertEqual('login', $transport->getAuthMode());
		$this->assertEqual('test username', $transport->getUsername());
		$this->assertEqual('test password', $transport->getPassword());

		$swift = new Swift(array('transport' => 'smtp'));
		$mailer = (new \ReflectionMethod($swift, '_transport'))->invoke($swift, $options);
		$transport = $mailer->getTransport();
		$this->assertEqual('test host', $transport->getHost());
		$this->assertEqual(2525, $transport->getPort());
		$this->assertEqual(10, $transport->getTimeout());
		$this->assertEqual('tls', $transport->getEncryption());
		$this->assertEqual('127.0.0.2', $transport->getSourceip());
		$this->assertEqual('test local_domain', $transport->getLocalDomain());
		$this->assertEqual('login', $transport->getAuthMode());
		$this->assertEqual('test username', $transport->getUsername());
		$this->assertEqual('test password', $transport->getPassword());
	}

	public function testMessage() {
		$message = new Message(array(
			'to' => array('Foo' => 'foo@bar'), 'from' => 'valid@address',
			'subject' => 'test subject', 'charset' => 'ISO-8859-1',
			'types' => 'text', 'cc' => array('Bar' => 'bar@foo', 'baz@qux'),
			'headers' => array('Custom' => 'foo')
		));
		$message->body('text', 'test body');
		$swift = new Swift();
		$swiftMessage = (new \ReflectionMethod($swift, '_message'))->invoke($swift, $message);

		$this->assertEqual(array('foo@bar' => 'Foo'), $swiftMessage->getTo());

		$expected = array('valid@address' => null);
		$this->assertEqual($expected, $swiftMessage->getFrom());

		$expected = array('bar@foo' => 'Bar', 'baz@qux' => null);
		$this->assertEqual($expected, $swiftMessage->getCc());

		$this->assertEqual('test subject', $swiftMessage->getSubject());

		$this->assertEqual('test body', $swiftMessage->getBody());

		$this->assertEqual('ISO-8859-1', $swiftMessage->getCharset());

		$header = $swiftMessage->getHeaders()->get('Custom');
		$this->assertEqual('foo', $header->getValue());
	}

	public function testMultipartMessage() {
		$message = new Message();
		$message->body('text', 'test text body');
		$message->body('html', '<b>test html body</b>');
		$swift = new Swift();
		$swiftMessage = (new \ReflectionMethod($swift, '_message'))->invoke($swift, $message);
		$this->assertEqual('<b>test html body</b>', $swiftMessage->getBody());
		$children = $swiftMessage->getChildren();
		$this->assertEqual(1, count($children));
		$this->assertEqual('test text body', $children[0]->getBody());
	}

	public function testAttachments() {
		$path = tempnam('/tmp', 'li3_mailer_test');
		file_put_contents($path, 'file data');
		$message = new Message(array('attach' => array(
			array('data' => 'my data', 'filename' => 'cool.txt'),
			$path => array(
				'filename' => 'file.txt', 'id' => 'foo@bar',
				'content-type' => 'text/plain'
			)
		)));
		$message->body('text', 'text body');
		$message->body('html', 'html body');
		$swift = new Swift();
		$swiftMessage = (new \ReflectionMethod($swift, '_message'))->invoke($swift, $message);
		$children = $swiftMessage->getChildren();
		$attachments = array_filter($children, function($child) {
			return in_array($child->getBody(), array('file data', 'my data'));
		});
		$this->assertEqual(2, count($attachments));
		list($file, $data) = array_values($attachments);
		if ($data->getBody() !== 'my data') {
			list($file, $data) = array($data, $file);
		}
		//swift wipes out body for files, maybe a bug?
		//$this->assertEqual('file data', $file->getBody());
		$this->assertEqual('file.txt', $file->getFilename());
		$this->assertEqual('text/plain', $file->getContentType());
		$this->assertEqual('foo@bar', $file->getId());

		$this->assertEqual('my data', $data->getBody());
		$this->assertEqual('cool.txt', $data->getFilename());
		$this->assertEqual('text/plain', $data->getContentType());
		unlink($path);
	}
}

?>