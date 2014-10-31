<div class="container login-container">
	<div class="row">
		<center>
			<div class="login-panel panel panel-default">

				<div class="panel-body">
					<form id="login_form" method="post" action="<?=$this->link('user', 'login');?>" >
						<fieldset>

						<?php if ( $error ) { ?>
							<div class="form-group has-error">
								<span class="help-block error">Неверный логин или пароль</span>
							</div>
						<?php } ?>

							<div class="form-group">
								<input class="form-control" placeholder="Логин" name="login" type="login" autofocus>
							</div>

							<div class="form-group">
								<input class="form-control" placeholder="Пароль" name="password" type="password" value="">
							</div>

							<button style="width:100%" data-ajax="false" class="btn btn-large btn-primary" data-disabled="false">Войти</button>
						</fieldset>
					</form>
				</div>
			</div>
		</center>
	</div>
</div>

<style type="text/css">
	body { background: url(img/system/bg-jeans-blue.jpg); }
</style>