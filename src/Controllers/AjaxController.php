<?php
/*
 */

namespace DSLive\Controllers;

use DScribe\Core\AController,
	Session;

/**
 * Description of DummyController
 *
 * @author topman
 */
class DummyController extends AController {
	public function ajaxAction() {
		$ajax = Session::fetch('ajax');
		Session::remove('ajax');
		$ajax->toScreen();
	}
}
