<html>
<head>
	<base href="{$config->root_url}/"/>
	<title>Simpla vQmod {$installer->mods->get('vqmod_control')->version}</title>
	
	<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
	<meta http-equiv="Content-Language" content="ru" />

	<meta http-equiv="cache-control" content="max-age=0" />
	<meta http-equiv="cache-control" content="no-cache" />
	<meta http-equiv="expires" content="0" />
	<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
	<meta http-equiv="pragma" content="no-cache" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	
	<link rel="stylesheet" href="vqmod/lib/design/bootstrap/css/bootstrap.min.css"/>
	<link rel="stylesheet" href="vqmod/lib/design/bootstrap/css/bootstrap-theme.min.css"/>
	<link rel="stylesheet" href="vqmod/lib/design/style.css"/>

	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
	
</head>
<body>
    <div class="site-wrapper">
      <div class="site-wrapper-inner">
        <div class="cover-container">
		
          <div class="masthead clearfix">
            <div class="inner">
              <a href="/"><h3 class="masthead-brand">Simpla vQmod {$installer->mods->get('vqmod_control')->version}</h3></a>
              <nav>
                <ul class="nav masthead-nav">
					<li{if $installer->action=='mods'} class="active"{/if}><a href="vqmod/mods">Mods</a></li>
					<li{if $installer->action=='manager'} class="active"{/if}><a href="vqmod/manager">Manager</a></li>
					<li><a href="https://github.com/yr4ik/Simpla-vqmod" target="_blank">GitHub</a></li>
					<li><a href="http://forum.simplacms.ru/topic/11871-237-vqmod-simplacms/" target="_blank">Forum</a></li>
                </ul>
              </nav>
            </div>
          </div>

          <div class="inner cover">
			<div class="lead">
				{$content}
			</div>
          </div>

        </div>
      </div>
    </div>
  <div class="mastfoot">
	  Simpla vQmod v{$installer->vqmod_version} &copy; <a href="http://vk.com/polevik_yuriy">Polevik Yurii</a>.
  </div>
	
	<script type="text/javascript">
	$(function(){
		$('button,input[type="button"]').filter(':not([class])').addClass('btn btn-default');
	});
	</script>
</body>
</html>