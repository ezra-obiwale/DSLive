<?php

/*
 */

namespace dsLive\Models;

/**
 * Description of SubscriberUser
 *
 * @author topman
 */
class SubscriberUser extends User {

	public function preSave() {
		$this->role = 'subscriber';
		parent::preSave();
	}

}
