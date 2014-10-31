<?php
class router {

	function delegate() {

		$_GET_KEYS = array_keys($_GET);
		$module = array_shift($_GET_KEYS);
		$map = explode('@', $module);
			// если в массиве $map есть первый элемент, то используем его как имя контроллера
			// если в масиве $map есть второй элемент, то записываем его как имя экшена
			// если массив $map пустой, то имя контроллера и имя экшена будет index
		$controllerName = ( !empty($map[0]) ) ? $map[0] : 'index';
		$actionName = ( !empty($map[1]) ) ? $map[1] : 'index';

		$template_folder = 'templates';
		$widget_folder = '';

			// check if module is widget, then render widget (get widget name from "action" variable, set action=index and set template folder to widget template)
		if ( $controllerName=='widget' ) {
			$widget = $actionName;

			if ( strpos($widget, '/')!==false ) {
				$widget_folder = substr($widget, 0, strrpos($widget, '/')) . '/';
				$widget = substr($widget, strrpos($widget, '/')+1);
			}
			$controller_class = $widget.'Widget';
			require_once(SITE_ROOT . '/widgets/' . $widget_folder . $widget . '/controller.php');

			$actionName = 'index';
			$template_path = SITE_ROOT . '/widgets/' . $widget_folder . $widget . '/template.tpl';
		} else {
			$controller_class = $controllerName.'Controller';
			$template_path = SITE_ROOT . '/' . $template_folder . '/' . $controllerName . '/' . $actionName . '.tpl';
		}

			// create controller instance
		$controller = new $controller_class($controllerName, $actionName);
		$controller->setContext(request::req('context', 'html'));

		$view =& template::getInstance();
		$view->setCurrentPage($controllerName, $actionName);

		if ( !is_callable(array($controller_class, $actionName)) ) {
			$controller->setTemplateName('404');
			$tpl_vars = array();
		} else {
				// take action
			$tpl_vars = $controller->$actionName();
		}

		header('Content-type: text/html; charset=utf-8');

			// context JSON == render all tpl variables as json object
			// context AJAX == render only html for current module (or widget)
			// context HTML == render main.tpl (with header, footer and content - DEFAULT VIEW)
		if ( $controller->getContext()=='json' ) {

			if ( isset($tpl_vars['result']) && $tpl_vars['result']==0 ) {

				$content = ( isset($tpl_vars['message']) ) ? jsonResponse(0, array('controller'=>$controller_class, 'action'=>$actionName), $tpl_vars['message']) : jsonResponse(0);

			} else {
				if ( empty($tpl_vars) ) {
					$tpl_vars = array();
				} else {
					foreach ($tpl_vars as $key => $value) {
						if ( gettype($tpl_vars[$key])=='object' && is_subclass_of($tpl_vars[$key], 'model') ) {
							$tpl_vars[$key] = $tpl_vars[$key]->toJson();
						} elseif ( gettype($tpl_vars[$key])=='array' ) {
							if ( !is_object(current($tpl_vars[$key])) || !is_subclass_of(current($tpl_vars[$key]), 'model') ) continue;

							$model_list = $tpl_vars[$key];
							$tpl_vars[$key] = array();

							foreach ($model_list as $model_key => $model_value) {
								$tpl_vars[$key][] = $model_value->toJson();
							}
						}
					}
				}

				if ( !empty(Tracy\Debugger::$ajaxDumpVars) ) {
					foreach (Tracy\Debugger::$ajaxDumpVars as $key=>$value) {
						Tracy\Debugger::ajax($value);
					}
					$tpl_vars['debug'] = substr(Tracy\Debugger::getBar()->getPanel('Tracy\\Debugger:ajax')->getPanel(), 55, -6);
				}

				$tpl_vars = array_merge($tpl_vars, array('controller'=>$controller_class, 'action'=>$actionName));
				$content = jsonResponse(1, $tpl_vars);
			}
			header('Content-type: application/json; charset=utf-8');

		} elseif ( $controller->getContext()=='ajax' ) {

			$view->setTemplate($template_path);
			$view->setVars($tpl_vars);
			$content = $view->render();

			if ( $controller->sendAsJson ) {
				$content = jsonResponse(1, array('html'=>$content));
				header('Content-type: application/json; charset=utf-8');
			}

		} elseif ( $controller->getContext()=='html' ) {

			if ( $controllerName==='widget' ) {
				$view->setTemplate($template_path);
				$view->setVars($tpl_vars);
			} else {
				$view->setContent($template_path);
				$view->setTemplate(SITE_ROOT . '/' . $template_folder . '/' . $controller->getTemplateName() . '.tpl');
				$view->setVars($tpl_vars);
			}

			$content = $view->render();
		}

		// if debug, output content, else gzip and output
		if ( config::get('DEBUG', 1) ) {
			echo $content;
		} else {
			header('content-encoding: gzip');
			echo gzencode($content);
		}
	}
}