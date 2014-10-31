<?php
class userController extends controller {

	public function login() {

			// Если пользователь уже залогинен, ничего не делаем
		if ( $this->user->getId() ) {
			$this->redirect( config::get('userDefaultController'), config::get('userDefaultAction') );
		}

		if ( !request::isPost() ) {
			return;
		}

		$login = request::post('login');
		$password = request::post('password');
		$error = $this->user->login($login, $password);

			// Если успешно, то перебрасываем на дефолтную страницу для зарегистрированного пользователя, иначе показываем /templates/user/login.tpl и ошибку
		if ( $error===0 ) {

			$this->redirect( config::get('userDefaultController'), config::get('userDefaultAction') );

		} else {
			return array(
				'error'=>'Неверный логин или пароль',
			);
		}
	}

	public function logout() {
		$this->user->logout();
		$this->redirect('user', 'login');
	}

}