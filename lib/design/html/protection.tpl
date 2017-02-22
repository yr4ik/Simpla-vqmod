<h1>vQmod Protection</h1>

<form method="post">

	<input type="hidden" name="rewrite" value="yes">
	<input type="hidden" name="action" value="{$installer->action|escape}">
	<input type="hidden" name="controller" value="{$installer->mod->name|escape}">

	<p>Здравствуйте!</p>
	<p>Для продолжения необходима авторизация<br>
	<small class="text-warning">(примечание: учетная запись должна иметь доступ к настрокам сайта)</small></p>
	<hr>
	
	<button type="submit" class="btn btn-primary">Продолжить</button>
	
</form>