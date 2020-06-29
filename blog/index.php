<?php
$authorized = false;

$redis = new Redis();
$redis->connect('127.0.0.1');

if(isset($_SERVER['HTTP_AUTHORIZATION'])) {
	$token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
	if($redis->get('avocado:z:token:'.$token)) {
		$authorized = true;
	}
}

if(!$authorized) {
	header('WWW-Authenticate: Bearer scope="read"');
	header('Link: <https://avocado.lol/blog/token.php>; rel="token_endpoint"');
	header('HTTP/1.1 200 Ok');
}
	
$public = [
	'one',
	'two',
	'three',
	'five',
];
$private = [
	'four'	
];

if($authorized) {
	$posts = array_merge($public, $private);
} else {
	$posts = $public;
}

foreach($posts as $post) {
	echo $post."\n";
}

