<?php
class controller {

	protected $context;		// output context (json, ajax, html)
	protected $template;	// template name for rendering (default is main.tpl)
	public $sendAsJson = false;
	public $controllerName = NULL;
	public $actioneName = NULL;
	public $user;			// current user object

	public function __construct($controllerName, $actionName) {
		$this->view =& template::getInstance();
		$this->setTemplateName('main');
		$this->setContext(request::req('context', 'html'));
		$this->user = $this->view->user = user::getCurrentUser();
		$this->controllerName = $controllerName;
		$this->actionName = $actionName;
        if ( isset($_REQUEST['m']) && $_REQUEST['m']==1 ) {
            $_SESSION['m'] = 1;
            request::setcookie('m', 1);
        }

        if ( isset($_REQUEST['m']) && $_REQUEST['m']==0 ) {
            $_SESSION['m'] = 0;
            request::setcookie('m', 0);
        }

        if ( (isset($_SESSION['m']) && $_SESSION['m']==1) || (isset($_COOKIE['m']) && $_COOKIE['m']==1) ) {
            $this->setTemplateName('main-mobile');
        } else {
            $this->setTemplateName('main');
        }
			// ��� ���� ��� �� ������� ���� ���� ��� �������������� ������������
		//if ( !$this->user->getId() && $this->getControllerName()!=='user' && $this->getActionName()!=='login' ) {
		//	$this->redirect('user', 'login');
		//}
	}

	public function setContext($context) {
		$this->context = ( in_array($context, array('json','ajax','html')) ) ? $context : 'html';
		Tracy\Debugger::$currentContext = $this->context;
	}

	public function getContext() {
		return ( !empty($this->context) ) ? $this->context : 'html';
	}

	public function setTemplateName($template) {
		$this->template = $template;
	}

	public function getTemplateName() {
		return $this->template;
	}

	public function getControllerName() {
		return $this->controllerName;
	}

	public function getActionName() {
		return $this->actionName;
	}

	public function redirect($controller = '', $action = '', $params = array()) {
		if ( mb_substr($controller, 0, 4)==='http' ) {
			header('Location: ' . $controller);
		} else {
			header('Location: ' . $this->view->link($controller, $action, $params));
		}

		exit();
	}

}