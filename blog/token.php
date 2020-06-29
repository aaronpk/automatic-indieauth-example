<?php
require('../vendor/autoload.php');

$redis = new Redis();
$redis->connect('127.0.0.1');

$http = new p3k\HTTP();

if(isset($_POST['grant_type']) && $_POST['grant_type'] == 'autoauth') {
	
	# I see a request from someone claiming to act on behalf of this user
	# $_POST['me']
	
	# Let's go find the authorization endpoint to verify the request
	$response = $http->get($_POST['me']);
	
	$authorization_endpoint = $response['rels']['authorization_endpoint'][0];
	
	# Ask the authorization endpoint if they made this request
	$response = $http->post($authorization_endpoint, [
		'request_type' => 'verify',
		'target_url' => $_POST['target_url'],
		'me' => $_POST['me'],
		'scope' => $_POST['scope'],
		'state' => $_POST['state'],
	]);

	if($response['code'] == 200) {

		$token = bin2hex(random_bytes(20));
		
		$redis->set('avocado:z:token:'.$token, json_encode([
			'target_url' => $_POST['target_url'],
			'me' => $_POST['me'],
			'scope' => $_POST['scope'],
		]));
		
		$http->post($authorization_endpoint, [
			'grant_type' => 'token',
			'token_type' => 'Bearer',
			'state' => $_POST['state'],
			'access_token' => $token,
			'scope' => $_POST['scope'], # if it has been changed
			'expires_in' => 86400,
		]);
				
	} else {
		die('authorization endpoint rejected this request');
	}
		
}

