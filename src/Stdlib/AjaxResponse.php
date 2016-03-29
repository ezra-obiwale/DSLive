<?php
/*
 */

namespace dsLive\Stdlib;

use Json;

/**
 * Description of AjaxResponse
 *
 * @author topman
 */
class AjaxResponse {
	/*
	 * Constants are in sync with dscribe.js ajax responses/actions. Any updates
	 * to constants must also reflect there for correct syncing
	 */

	/**
	 * Indicates the request failed
	 */

	const STATUS_FAILURE = 0;
	/**
	 * Indicates the request succeeded
	 */
	const STATUS_SUCCESS = 1;
	/**
	 * Indicates that the browser may continue to the next phase
	 */
	const ACTION_CONTINUE = 0;
	/**
	 * Indicates the browser should wait for action from user
	 */
	const ACTION_WAIT = 1;
	/**
	 * Indicates the browser should reset data and wait for action from user
	 */
	const ACTION_RESET_WAIT = 2;

	protected $status;
	protected $message;
	protected $action;
	protected $errors;

	/**
	 * Class constructor
	 * @param int $status Status of the request to send in the reponse @see setStatus()
	 * @param mixed $message Message to send in the response @see setMessage()
	 * @param int $action Instruction to send with the response @see setAction()
	 */
	public function __construct($message = null, $status = self::STATUS_SUCCESS, $action = self::ACTION_CONTINUE) {
		$this->setStatus($status)->setMessage($message)->setAction($action);
		$this->errors = array();
	}

	/**
	 * Fetches the status of the response
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Sets the status of the response to send to the request
	 * @param int $status Any of the constants FAILURE_STATUS, SUCCESS_STATUS
	 * @return \dsLive\Stdlib\AjaxResponse
	 */
	public function setStatus($status) {
		$this->status = $status;
		return $this;
	}

	/**
	 * Fetches the message of the response
	 * @return mixed
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Sets the message to send in the response
	 * @param mixed $message
	 * @return \dsLive\Stdlib\AjaxResponse
	 */
	public function setMessage($message) {
		$this->message = $message;
		return $this;
	}

	/**
	 * Fetches the action to send with the response
	 * @return int Any of the constants CONTINUE_ACTION, WAIT_ACTION, CLEAR_WAIT_ACTION
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * Sets the action to send with the response
	 * @param int $action Any of the constants CONTINUE_ACTION, WAIT_ACTION, CLEAR_WAIT_ACTION
	 * @return \dsLive\Stdlib\AjaxResponse
	 */
	public function setAction($action) {
		$this->action = $action;
		return $this;
	}

	/**
	 * Fetches the errors of the response
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Sets the errors of the response
	 * @param array $errors
	 */
	public function setErrors($errors) {
		$this->errors = $errors;
		return $this;
	}

	/**
	 * Sends the response in json format
	 *
	 * @param mixed $message Message to send in the response
	 * @param int $status Status of the request to send in the reponse
	 * @param int $action Instruction to send with the response
	 */
	public function sendJson($message = null, $status = null, $action = null) {
		if ($message !== null)
			$this->setMessage($message);
		if ($status !== null)
			$this->setStatus($status);
		if ($action !== null)
			$this->setAction($action);

		// @todo find a way to send to screen even when some content has been posted
		\Session::save('ajax', $this);
		header('location: /in/ajax/ajax');
		exit;

//		return $this->toScreen();
	}

	public function toScreen() {
		$json = new Json(array(
				'message' => $this->getMessage(),
				'status' => $this->getStatus(),
				'action' => $this->getAction(),
				'errors' => $this->getErrors(),
			));

		return $json->toScreen(true);
	}

}
