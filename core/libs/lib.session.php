<?php
namespace core\libs;

use console;

class session {

	private static $started = false;

	public static function close() {
		if (self::$started) {
			// session_destroy();
			session_write_close();
		}
	}

	public static function start() {
		if (!self::$started) {
			session_start();
			self::$started = true;
		}
		return session_id();
	}

	public static function setId($session_id) {
		if ($session_id !== session_id()) {
			if (self::$started) {
				session_write_close();
			}
			session_id($session_id);
			session_start();
			self::$started = true;
		}
	}

	public static function restart() {
		if (self::$started) {
			session_regenerate_id();
		}
		session_start();
		self::$started = true;
	}


	private $session_id;

	public function getId() {
		return $this->session_id;
	}

	private function checkSession() {
		self::setId($this->session_id);
		/* if (!self::$started) {
			self::start();
		} else if ($this->session_id) { // && $this->session_id !== session_id()) {
			self::setId($this->session_id);
		} */
	}

	public function __construct($session_id = null) {
		$session_id = $session_id ?? self::start();
		$this->session_id = $session_id;
		if (!isset(self::$sessions[$session_id])) {
			self::$sessions[$session_id] = $this;
		}
	}

	private static $sessions = [];

	public static function getInstance($session_id = null) {
		$session_id = $session_id ?? self::start();
		if (!isset(self::$sessions[$session_id])) {
			return new session($session_id);
		}
		return self::$sessions[$session_id];
	}

	public static function getChannel($channel) {
		$session = self::getInstance();
		return $session->channel($channel);
	}

	public function set($key, $value) {
		$this->checkSession();
		$_SESSION[$key] = $value;
	}

	public function get($key) {
		$this->checkSession();
		return $_SESSION[$key] ?? null;
	}

	public function isset($key) {
		$this->checkSession();
		return isset($_SESSION[$key]);
	}

	public function unset($key) {
		$this->checkSession();
		unset($_SESSION[$key]);
	}

	private $channels = [];

	public function channel($channel) {
		if (!isset($this->channels[$channel])) {
			$this->channels[$channel] = new sessionChannel($this, $channel);
		}
		return $this->channels[$channel];
	}

}

class sessionChannel {

	private $session;

	private $prefix;

	public function __construct($session, $prefix = 'global') {
		$this->session = $session;
		$this->prefix = $prefix.'.';
	}

	public function __set($key, $value) {
		$this->session->set($key, $value);
	}

	public function __get($key) {
		return $this->session->get($key);
	}

	public function __isset($key) {
		return $this->session->isset($key);
	}

	public function __unset($key) {
		$this->session->unset($key);
	}

}


/* $session = session::getInstance($msg->id_session);

$ss = $session->channel('global');

$ss->variable = 1; // set

$variable = $ss->variable; // get */




/* $session = new session('35345435'); // prefix, session_id
$abc = $session->abc;
$session->abc = 2; */