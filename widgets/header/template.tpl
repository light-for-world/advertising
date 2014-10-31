
<nav class="navbar navbar-inverse navbar-static-top" role="navigation">
	<div class="container">
		<!-- Brand and toggle get grouped for better mobile display -->
		<div class="navbar-header">
			<a class="navbar-brand" href="/">Crystal</a>
		</div>

		<div class="navbar-collapse">
			<ul class="nav navbar-nav">
				<li<?php $this->_print(' class="active"', $this->getControllerName()==='index')?>>
					<a href="<?php print $this->link('index')?>">Реестр замен</a>
				</li>
				<li<?php $this->_print(' class="active"', $this->getControllerName()==='sale')?>>
					<a href="<?php print $this->link('sale')?>">Продажи</a>
				</li>
				<li<?php $this->_print(' class="active"', $this->getControllerName()==='payment')?>>
					<a href="<?php print $this->link('payment')?>">Выплаты</a>
				</li>
				<li<?php $this->_print(' class="active"', $this->getControllerName()==='client')?>>
					<a href="<?php print $this->link('client')?>">Клиенты</a>
				</li>
				<li<?php $this->_print(' class="active"', $this->getControllerName()==='agent')?>>
					<a href="<?php print $this->link('agent')?>">Агенты</a>
				</li>
			</ul>
		</div><!-- /.navbar-collapse -->
	</div>
</nav>
