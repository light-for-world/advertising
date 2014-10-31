<?php
class systemController extends controller {

	public function clearCache() {
		apc_clear_cache('user');
		$_COOKIE['debug'] = 1;
		config::set('DEBUG', 1);
		print_arr('Кеш очищен!', 1);
	}
}