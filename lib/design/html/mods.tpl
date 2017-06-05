<h1>Управление компонентами</h1>


{if $mods}
<table class="table table-condensed components">
	<tr>
		<th class="col-xs-7">Компонент</th>
		<th class="col-xs-1 text-center">Версия</th>
		<th class="col-xs-1 text-center">Состояние</th>
		<th class="col-xs-1"></th>
	</tr>
	
	{foreach $mods as $mod}
	<tr>
		<td>
			<span{if $mod->description} title="{$mod->description|escape}"{/if}>{$mod->name}</span>
			{if $mod->author}
			<div class="small">
				Автор: 
				{if $mod->author_url}
					<a href="{$mod->author_url}" target="_blank" title="Открыть сайт автора">{$mod->author}</a>
				{else}
					{$mod->author}
				{/if}
			</div>
			{/if}
		</td>
		<td class="text-center">{$mod->version|default:'-'}</td>
		<td class="text-center">
			{if $mod->status == 'installed'} 
				<i class=" glyphicon glyphicon-ok text-success" title="Устновлен{if $mod->install_timestamp} {$mod->install_timestamp|date_format:'%d.%m.%Y %T'}{/if}">
			{elseif $mod->status == 'uninstalled'} 
				<i class=" glyphicon glyphicon-remove text-warning" title="Не установлен">
			{else}
				<i class=" glyphicon glyphicon-question-sign text-info" title="Не известно">
			{/if}
		</td>
		<td class="text-right actions">
			{if $mod->log_exist}
				<a href="{$mod->log_file}" class="log-view fancybox.iframe" title="Log-файл"><i class="glyphicon glyphicon-list-alt"></i></a>
			{/if}
			<a href="vqmod/{$mod->id}/install" title="{if $mod->status == 'installed'}Переустановить{else}Установить{/if}"><i class="glyphicon glyphicon-{if $mod->status == 'installed'}refresh{else}plus-sign{/if}"></i></a>
			<a href="vqmod/{$mod->id}/uninstall" title="Удалить"><i class="glyphicon glyphicon-minus-sign"></i></a>
		</td>
	</tr>
	{/foreach}
	
</table>


<script type="text/javascript" src="js/fancybox/jquery.fancybox.pack.js"></script>
<link rel="stylesheet" href="js/fancybox/jquery.fancybox.css" type="text/css" media="screen" />

<script type="text/javascript">
	$(function(){
		$('a.log-view').fancybox();
	});
</script>

{else}
	<div class="alert alert-danger">Компоненты отсутствуют</div>
{/if}
