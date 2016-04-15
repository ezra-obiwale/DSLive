<?php

/*
 * The MIT License
 *
 * Copyright 2015 Ezra.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace dsLive\Controllers;

use dbScribe\Table,
	dsLive\Services\SmsService,
	Object;

/**
 * Description of SmsController
 *
 * @author Ezra
 */
abstract class SmsController extends SuperController {

	public function accessRules() {
		return array(array('allow', array('role' => 'admin')), array('deny'));
	}

	public function init() {
		parent::init();
		$this->service = new SmsService(FALSE);
	}

	protected function getViewPath($filePath) {
		$path = str_ireplace(VENDOR, '', dirname(__DIR__));
		return $path . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'views'
				. DIRECTORY_SEPARATOR
				. str_replace('/', DIRECTORY_SEPARATOR, $filePath);
	}

	public function indexAction() {
		$this->view->variables(array(
			'balanceInfo' => array(
				'lastPurchaseDate' => '14-Dec-2015',
				'lastPurchaseAmount' => 'N12000',
				'lastPurchaseUnit' => '12000',
				'totalUnitsUsed' => '600',
				'unitsAvailable' => '11400'
			)
		))->file($this->getViewPath('sms/index'), true);
	}

	public function historyAction() {
		$this->view->partial()->file($this->getViewPath('sms/drafts'), true);
	}

	public function checkBalanceAction() {
		$this->view->partial();
	}

	public function purchaseCreditAction() {
		$this->view->partial()->file($this->getViewPath('sms/purchase-credit'), true);
	}

	public function draftsAction() {
		$this->view->variables(array(
			'messages' => $this->service->getRepository()
					->orderBy('timestamp', Table::ORDER_DESC)
					->notEqual(array(array('is_template' => true)))
					->fetchAll(Table::RETURN_DEFAULT),
		))->partial()->file($this->getViewPath('sms/drafts'), true);
	}

	public function newAction($id = null) {
		$post = new Object();
		if ($this->request->isPost()) {
			$post = $this->request->getPost();
			// send message to recipients
		}
		else if ($id) { // initiate sending of draft
			$post = $this->service->findOne($id);
		}
		$this->view->variables(array(
			'post' => $post->toArray(),
			'users' => $this->getUserList(),
		))->partial()->file($this->getViewPath('sms/new'), true);
	}

	public function viewAction($id) {
		parent::viewAction($id)->partial()
				->file($this->getViewPath('sms/view'), true);
	}

	/**
	 * @return array of ids as keys, fullNames (required), phoneNumbers (required),
	 * [others] as index-based value arrays
	 */
	abstract protected function getUserList();
}
