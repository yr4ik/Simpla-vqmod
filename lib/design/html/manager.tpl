<h1>Xml менеджер</h1>

{if isset($turn_xml)}
	{if $turn_xml->active}
		<div class="alert alert-success">Компонет {$turn_xml->id} включен</div>
	{else}
		<div class="alert alert-danger">Компонент {$turn_xml->id} выключен</div>
	{/if}
{/if}

<table class="table table-condensed components">
	<tr>
		<th class="col-xs-7">Xml</th>
		<th class="col-xs-2 text-center">Версия</th>
		<th class="col-xs-2 text-center">Состояние</th>
		<th></th>
	</tr>
	{foreach $xmls as $xml}
	<tr>
		<td>{$xml->id}{if $xml->author}<div class="small">Автор: {$xml->author}</div>{/if}</td>
		<td class="text-center">{$xml->version|default:'-'}</td>
		<td class="text-center"><i class="glyphicon {if $xml->active}glyphicon-ok text-success{else}glyphicon-remove text-warning{/if}" title="{if $xml->active}Включено{else}Выключено{/if}"></i></td>
		<td class="text-right actions">
			{if $xml->id == 'VQMOD_CORE_SIMPLACMS'}
			<i class="glyphicon glyphicon-lock text-warning" title="Управление заблокированно"></i>
			{else}
			<a href="vqmod/manager?turn={$xml->xml_file|escape:url}" title="{if $xml->active}Выключить{else}Включить{/if}"><i class="glyphicon glyphicon-{if $xml->active}minus{else}plus{/if}-sign"></i></a>
			{/if}
		</td>
	</tr>
	{/foreach}
</table>
