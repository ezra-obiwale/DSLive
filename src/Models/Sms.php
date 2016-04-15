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

namespace dsLive\Models;

/**
 * Description of Sms
 *
 * @author Ezra
 */
class SmsDrafts extends Model {

	/**
	 * @DBS\Timestamp
	 */
	protected $timestamp;

	/**
	 * @DBS\String (size=160)
	 */
	protected $message;

	/**
	 * @DBS\String (nullable=true)
	 */
	protected $recipients;

	/**
	 * @DBS\Boolean (default=false, nullable=true)
	 */
	protected $isTemplate;

	public function getTimestamp() {
		return $this->timestamp;
	}

	public function getMessage() {
		return $this->message;
	}

	public function getRecipients() {
		return $this->recipients;
	}

	public function setTimestamp($timestamp) {
		$this->timestamp = $timestamp;
		return $this;
	}

	public function setMessage($message) {
		$this->message = $message;
		return $this;
	}

	public function setRecipients($recipients) {
		$this->recipients = $recipients;
		return $this;
	}

	public function getIsTemplate() {
		return $this->isTemplate;
	}

	public function setIsTemplate($isTemplate) {
		$this->isTemplate = $isTemplate;
		return $this;
	}

	public function preSave($createId = true) {
		$this->timestamp = \dbScribe\Util::createTimestamp();
		if (is_array($this->recipients)) $this->recipients = json_encode($this->recipients);
		parent::preSave($createId);
	}

	public function postFetch($property = null) {
		parent::postFetch($property);
		$this->afterFetch();
	}

	protected function afterFetch() {
		if ($this->recipients) $this->recipients = json_decode($this->recipients, true);
	}

}
