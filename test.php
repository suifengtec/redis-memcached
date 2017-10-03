<?php

/**
 * @Author: suifengtec
 * @Date:   2017-10-03 15:29:45
 * @Last Modified by:   suifengtec
 * @Last Modified time: 2017-10-03 15:55:47
 **/
header("Content-type:text/html;charset=utf-8");

$records = 100000;

global $use_php_memcache,$use_php_memcacheD;
$redis = false;
$memcached = false;

$use_php_memcache = class_exists('Memcache')?true:false;
$use_php_memcacheD = class_exists('Memcached')?true:false;

if($use_php_memcacheD){

	$memcached = new Memcached();
	$memcached->addServer('127.0.0.1', 11211);

}

if(!$memcached&&$use_php_memcache){

	$memcached = new Memcache();
/*	$memcached->connect('127.0.0.1');
*/
	$memcached->addServer('127.0.0.1', 11211, true, 1);
}

if(class_exists('Redis')){

	$redis = new Redis();
	$redis->connect('127.0.0.1', 6379);
}


if(!$redis||!$memcached){

	die('PHP error: Redis or memcache/memcached');
}



function memcache_set_multi($args){

	global $use_php_memcache,$use_php_memcacheD,$memcached;
	if($use_php_memcacheD){

		$memcached->getMulti($args);
	}else{

		$arr = [];

		foreach($args as $k=>$v){

			$memcached->get($k,$v);
		}
	}

}
function memcache_get_multi($args){

	global $use_php_memcache,$use_php_memcacheD,$memcached;
	$r = [];
	if($use_php_memcacheD){

		$r = $memcached->setMulti($args);
	}else{

		

		foreach($args as $k=>$v){

			$r[$k]  = $memcached->get($k);
		}
	}

	return $r;
}
$array = array();
for($i=0; $i<$records; $i++)
{
    $value = sha1(mt_rand(10000,20000));
    $key = "key".$i;
    $array[$key] = $value;
    $arrayKeys[] = $key;
}
//multi set
$startRedis = microtime(true);
$redis->mset($array);
$endRedis = microtime(true) - $startRedis;
$startMemcached = microtime(true);

/*$memcached->setMulti($array);
*/
memcache_get_multi($array);


$endMemcached = microtime(true) - $startMemcached;
echo '<p>Redis multi set: '.$endRedis."</p>";
echo '<p>Memcached multi set: '.$endMemcached."</p>";
//multi get
$startRedis = microtime(true);
$result = $redis->mget($arrayKeys);
$endRedis = microtime(true) - $startRedis;
$startMemcached = microtime(true);

/*$result = $memcached->getMulti($arrayKeys);*/
$result = memcache_get_multi($arrayKeys);
$endMemcached = microtime(true) - $startMemcached;


/*

Redis multi set: 0.089952945709229

Memcached multi set: 1.9566721916199

Redis multi get: 0.092521190643311

Memcached multi get: 0.86802196502686

 */

echo '<p>Redis multi get: '.$endRedis."</p>";
echo '<p>Memcached multi get: '.$endMemcached."</p>";