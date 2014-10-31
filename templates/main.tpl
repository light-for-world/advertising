<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="shortcut icon" href="favicon.ico" />

		<?php echo $this->getMetatags(); ?>

		<title><?php echo $this->getTitle(); ?></title>

	<?php // Подключение стилей. Все стили подключаются через функцию headStyle. Если режим не дебаг, то перед отправкой в браузер, они собираются в один файл и архивируются
		echo $this->headStyle(
			array(
				'css/vendor/bootstrap.min.css',
				'css/vendor/font-awesome.min.css',
				'css/vendor/chosen.min.css',
				'css/system.css',
				'css/system_icons.css',
				'css/main.css',
			)
		);

			// Аналогично с JS. Подключаются через функцию headScript
		$this->headScript(
			array(
				'js/vendor/jquery-1.11.1.min.js',
				'js/vendor/jquery-ajax-form.js',
				'js/vendor/jquery.chosen.min.js',
				'js/vendor/handlebars.js',
				'js/vendor/moment.min.js',
				'js/vendor/bootstrap.js',
				'js/elly-core.js',
				'js/app.js',
			)
		);

		echo $this->attachStyle();
		echo $this->attachScript();
	?>
</head>

<body id="global_controller_<?php echo $this->getControllerName() . ' global_action_' . $this->getControllerName() . '_' . $this->getActionName(); ?>">


<?php echo $this->widget('header'); ?>

<?php echo $this->content(); ?>

<?php echo $this->widget('footer'); ?>


	<!-- иконка рядом с курсором при аякс запросе -->
<img src="img/system/ajax-mouse-loading.gif" id="elly-ajax-loading" alt="" style="display:none;" />

	<!-- шаблон для модального окна -->
<div class="modal fade" id="elly-modal-container" tabindex="-1" role="dialog" aria-labelledby="elly-modal-title" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title" id="elly-modal-title"></h4>
			</div>
			<div class="modal-body" id="elly-modal-body"></div>
		</div>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function(e) {
		// Плаг для работы с датами в js.
	moment.lang('ru');

});
</script>

</body>
</html>