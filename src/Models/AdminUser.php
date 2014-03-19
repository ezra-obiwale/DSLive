<?php
/*
 */

namespace DSLive\Models;

/**
 * Description of AdminUser
 *
 * @author topman
 */
class AdminUser extends User {

	public function preSave() {
		$this->role = 'admin';
		parent::preSave();
	}

}
