<?php
class user extends model {

	protected static $_tableName = 'users';

	protected static $_modelName = '';
	protected static $_rowIdentity = '';
	protected static $_dateFields = array();
	protected static $_tableStructure = array();

	private $_password = NULL;


	/**
	 * Get user visible name by existing users data
	 * @return string
	*/
	public function getName() {
		if ( $this->_row['fio'] ) {
			return $this->_row['fio'];
		} elseif ( $this->_row['login'] ) {
			return $this->_row['login'];
		} elseif ( $this->_row['email'] ) {
			return $this->_row['email'];
		}
	}

	/**
	 * Used for encrypt user password (currently no encrypting)
	 * @return string
	*/
	public function password_encrypt($password) {
		return $password;
	}

	/**
	 * Get password
	 * @return string
	*/
	public function getPassword() {
		return $this->_password;
	}

	/**
	 * Set password
	*/
	public function setPassword($password) {
		$this->_password = $password;
	}

	/**
	 * Logout current user and clear cookies
	*/
	public function logout() {
		$this->clearCookies();
	}

	/**
	 * Check user identity and password, set cookies, set error if wrong password
	 * @param  string $user_identity
	 * @param  string $password
	 * @return bool
	*/
	public static function login($user_identity, $password) {
		$user = self::get($user_identity, config::get('userIdentity', 'login'));
		$error = false;

		if ( empty($user) ) {
			$error = 10;
		} else {
			if ( $user->getPassword()!=$user->password_encrypt($password) ) {
				$error = 11;
			}
		}

		if ( $error ) {
			return $error;
		}

		$user->setCookies();
		return 0;
	}

	/**
	 * Set session variables for user (identity and password)
	*/
	public function setCookies() {
		setcookie('SESSION_NUMBER', $this->getId(), time() + (10 * 365 * 24 * 60 * 60));
		setcookie('PHPSESSION', md5(md5(md5($this->getPassword())) . $this->getName()), time() + (10 * 365 * 24 * 60 * 60));
		$_SESSION['code_login'] = $this->getId();
	}

	/**
	 * Clear session variables for user (identity and password)
	*/
	private function clearCookies() {
		setcookie('SESSION_NUMBER', 0, time() - 600);
		setcookie('PHPSESSION', 0, time() - 600);
		unset($_COOKIE['SESSION_NUMBER']);
		unset($_COOKIE['PHPSESSION']);
	}

	/**
	 * Check user cookies
	 * @return bool
	*/
	public function checkCookies() {
		return ( $this->getId() && $_COOKIE['SESSION_NUMBER']==$this->getId() && $_COOKIE['PHPSESSION']==md5(md5(md5($this->getPassword())) . $this->getName()) );
	}

	/**
	 * Get user avatar (return default avatar if no image)
	 * @return string
	*/
	public function getAvatar($type = '') {

		if ( $type=='' ) {

			return ( !empty($this->_row['avatar']) )
						? $this->_row['avatar']
						: 'img/user_no_photo.png' ;

		} else if ( isset($this->_row['avatar'][$type]) ) {

			return $this->_row['avatar'][$type]['path'];

		} else {

			return 'public/avatar/' . $this->_row['avatar'];

		}

	}

	/**
	 * Get user avatar (return default avatar if no image)
	 * @return string | bool
	*/
	public function uploadAvatar($file) {
		if ( $file['error']!=UPLOAD_ERR_OK ) {

			return false;

		} else {
			$avatar = $this->avatar;

			if ( !empty($avatar) && file_exists(SITE_ROOT . '/uploads/avatars/' . $avatar) ) {
				unlink(SITE_ROOT . '/uploads/avatars/' . $avatar);
			}
			$avatar = $this->getId() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);

			if ( move_uploaded_file($file['tmp_name'], 'uploads/avatars/' . $avatar) ) {
				return $avatar;
			} else {
				return false;
			}

		}
	}

	public function delete() {
		$this->status = 0;
		$this->update();
	}

	/**
	 * Get current loggined user
	 * @return User object
	*/
	public static function &getCurrentUser() {
		static $instance;
		if( is_null($instance) ) {
			if ( !empty($_COOKIE['SESSION_NUMBER']) ) {
				$instance = self::getUser($_COOKIE['SESSION_NUMBER']);
				if ( !$instance || !$instance->checkCookies() ) {
					$instance = new user;
				}
			} else {
				$instance = new user;
			}
		}
		return $instance;
	}

	/**
	 * Get user by identity from static cache, or from APC cache
	 * @return User object
	*/
	public static function &getUser($identity) {

		if ( false === ( $user = cache::getStatic('user', $identity) ) ) {
			$user = self::get($identity);
			cache::setStatic('user', $identity, $user);
		}
		if ( !$user ) {
			$user = new user;
		}
		return $user;
	}

	public function toArray() {
		$data = parent::toArray();
		$data['password'] = $this->getPassword();
		return $data;
	}

	public function toJson() {
		$data = $this->toArray();
		unset($data['password']);
		$data['avatar'] = $this->getAvatar();
		return $data;
	}

	public function setFromArray($data) {
		parent::setFromArray($data);

		if ( isset($data['password']) ) {
			$this->setPassword($data['password']);
			unset($this->_row['password']);
		}
	}

}