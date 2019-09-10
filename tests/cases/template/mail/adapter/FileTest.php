<?php

namespace li3_mailer\tests\cases\template\mail\adapter;

use li3_mailer\template\mail\adapter\File;
use li3_mailer\template\helper\mail\Html;
use li3_mailer\net\mail\Message;
use li3_mailer\tests\mocks\template\MailWithoutRender;
use li3_mailer\tests\mocks\template\mail\adapter\File as FileMock;
use lithium\net\http\Router;

class FileTest extends \lithium\test\Unit {
	public function setDefaultRoute() {
		$this->_routes = Router::get();
		Router::reset();
		Router::connect('/{:controller}/{:action}');
	}

	public function resetRoutes() {
		Router::reset();

		foreach ($this->_routes as $route) {
			Router::connect($route);
		}
	}

	public function testInitialization() {
		$file = new File();

		$expected = array(
			'url', 'path', 'options', 'title', 'scripts', 'styles', 'head'
		);
		$result = array_keys($file->handlers());
		$this->assertEqual($expected, $result);

		$expected = array(
			'content' => '', 'scripts' => array(),
			'styles' => array(), 'head' => array()
		);
		$this->assertEqual($expected, $file->context());
	}

	public function testCoreHandlers() {
		$message = new Message(array('baseURL' => 'foo.local'));
		$file = new File(compact('message'));

		$this->setDefaultRoute();
		$url = $file->applyHandler(null, null, 'url', array(
			'controller' => 'foo', 'action' => 'bar'
		));
		$this->assertEqual('http://foo.local/foo/bar', $url);
		$this->resetRoutes();

		$helper = new Html();
		$class = get_class($helper) . "::script";
		$path = $file->applyHandler($helper, $class, 'path', 'foo/file');
		$this->assertEqual('http://foo.local/js/foo/file.js', $path);
		$this->assertEqual(
			'http://foo.local/some/generic/path',
			$file->path('some/generic/path')
		);
		$this->assertPattern(
			'/^cid:[^@]+@foo.local$/',
			$file->path('image.png', array('embed' => true, 'check' => false))
		);
	}

	public function testHelperNamespace() {
		$file = new File();
		$helper = $file->helper('html');
		$this->assertTrue($helper instanceof Html);
		//test cache
		$helper2 = $file->helper('html');
		$this->assertEqual($helper2, $helper);
	}

	public function testBadHelper() {
		$this->assertException('Mail helper `foo` not found.', function () {
			$file = new File();
			$helper = $file->helper('foo');
		});
	}

	public function testMessage() {
		$message = new Message();
		$file = new File(compact('message'));
		$this->assertEqual($message, $file->message());
	}

	public function testRenderDoesNotSetLibrary() {
		$view = new MailWithoutRender();
		$file = new FileMock(compact('view'));
		$params = $file->__render('element', 'foo');
		extract($params);
		$this->assertFalse(isset($options['library']));
	}

	public function testHandlers() {
		$file = new File();

		$this->assertEmpty(trim($file->scripts()));
		$this->assertEqual('foobar', trim($file->scripts('foobar')));
		$this->assertEqual('foobar', trim($file->scripts()));

		$this->assertEmpty(trim($file->styles()));
		$this->assertEqual('foobar', trim($file->styles('foobar')));
		$this->assertEqual('foobar', trim($file->styles()));

		$this->assertEmpty(trim($file->head()));
		$this->assertEqual('foo', trim($file->head('foo')));
		$this->assertEqual("foo\n\tbar", trim($file->head('bar')));
		$this->assertEqual("foo\n\tbar", trim($file->head()));
	}
}

?>