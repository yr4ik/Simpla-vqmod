<?php


error_reporting(0);

#VQMOD#
require_once('../vqmod/vqmod.php');
VQMod::bootup();
#VQMOD_END#


include_once(VQMod::modCheck(VQMod::getCwd() . 'api/Simpla.php'));
$simpla = new Simpla();


$sURL = $_SERVER['REQUEST_URI'];
$purl = parse_url($simpla->config->root_url);

if (isset($purl['path']) && $purl['path']!=='/')  
	$sURL = str_replace($purl['path'], '', $sURL); 

$sourceFile = $simpla->config->root_dir . ltrim($sURL, '/');


if (!file_exists($sourceFile)) // Не найден исходник для кэширования.
{
	header("http/1.0 404 not found");
	exit; 
}

$sourceFile = VQMod::modCheck($sourceFile);
$sourceExt = strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION));

$is_minify = strtolower(substr($sourceFile, -(strlen($sourceExt)+4), 3))=='min';//Если это уже сжатая версия


// Отдаем заголовоки
header('Content-type: ' . ($sourceExt=='css' ? 'text/css' : 'text/javascript'));


// если указано, что браузер принимает сжатие
$bGzip = '';
if($simpla->config->minify_gzip_level > 0 && isset($_SERVER['HTTP_ACCEPT_ENCODING']))
{
	if (stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)
	{
		if(function_exists('ob_gzhandler') && !ini_get('zlib.output_compression'))
		{
			$bGzip = '.gz';
			header('Content-Encoding: gzip');
		}
	}
}




$minify = 0;
if(!$is_minify)
{
	if($sourceExt=='css' && $simpla->config->minify_css)
		$minify = 1;
	elseif($sourceExt=='js' && $simpla->config->minify_js)
		$minify = 1;
}

if(!$minify && !$bGzip)// Все выключено просто отдаем файл
	die(file_get_contents($sourceFile));


// Кеширование
$cache_path = $simpla->config->root_dir . $simpla->config->minify_cache_dir;

$sCachedName = str_replace('/', '%', $sURL).$bGzip; 
$settings_hash = (filemtime($sourceFile)+$minify+$simpla->config->minify_gzip_level);
$cacheFile = $cache_path . base_convert($settings_hash, 10, 36) . '_' . $sCachedName;

// Проверяем кеш
if(!file_exists($cacheFile))
{
	if(!is_dir($cache_path)) 
		mkdir($cache_path, 0755, true);
	else
		foreach(glob($cache_path . '*_'.$sCachedName) as $old_cache_file)
			unlink($old_cache_file);

	if($minify)
	{
		// Подключаем библиотеки
		require_once 'MatthiasMullie/autoload.php';
		
		if($sourceExt=='css')
			$minifier = new MatthiasMullie\Minify\CSS($sourceFile);
		else
			$minifier = new MatthiasMullie\Minify\JS($sourceFile);
		
		if($bGzip)
			$content = $minifier->gzip(null, $simpla->config->minify_gzip_level);
		else
			$content = $minifier->minify();
	}
	else //Включено только сжатие
	{
		$content = gzencode(file_get_contents($sourceFile), $simpla->config->minify_gzip_level, FORCE_GZIP);
	}
	
	file_put_contents($cacheFile, $content);
	echo $content;
} 
else 
{
	readfile($cacheFile);
}

