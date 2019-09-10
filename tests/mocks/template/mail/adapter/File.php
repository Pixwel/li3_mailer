<?php
namespace li3_mailer\tests\mocks\template\mail\adapter;

class File extends \lithium\template\view\adapter\File {

	public function __render($type, $template, array $data = array(), array $options = array()) {
        return parent::_render($type, $template, $data, $options);
    }
}

?>