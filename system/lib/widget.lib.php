<?php
class widget extends controller {

	/**
		* Object of current loggined user
		* @var User object
	*/
	public $user;


	public function __construct() {
		$this->view = new template;
		$this->user = $this->view->user = user::getCurrentUser();
	}

}