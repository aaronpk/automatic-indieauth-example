<?php
require('../vendor/autoload.php');
header('Content-type: application/json');

// TODO: authorize this request
// (User has somehow allowed their reader to generate these tokens)


$redis = new Redis();
$redis->connect('127.0.0.1');


if(isset($_POST['request_type']) && $_POST['request_type'] == 'external_token') {
	
	$request_id = bin2hex(random_bytes(10));
	
	$redis->setex('avocado:u:request:'.$request_id, 60, 'pending');
	
	echo json_encode([
		'request_id' => $request_id,	
		'interval' => 5,
	]);
	
	
	// TODO: move this async later
	
	$http = new p3k\HTTP();
	$response = $http->get($_POST['target_url']);
	
	if(isset($response['rels']['token_endpoint'][0])) {
		$token_endpoint = $response['rels']['token_endpoint'][0];
	
		$state = bin2hex(random_bytes(10));
		
		$request = [
			# hey i'm trying to get an access token 
			'grant_type' => 'autoauth',
			
			# ... to fetch this resource URL
			'target_url' => $_POST['target_url'],
	
			# I'm acting on behalf of this user (which you can verify by looking them up at this URL)
			'me' => 'https://avocado.lol/user/',
		
			# The resource said I needed to ask for this scope
			'scope' => $_POST['scope'],
	
			# Please include this random string when you deliver the token
			'state' => $state,
		];
		
		$redis->setex('avocado:u:request:state:'.$state, 60, json_encode(array_merge(
			$request, ['request_id' => $request_id]
		)));
		
		$http->post($token_endpoint, $request);
	
	}

}


if(isset($_POST['request_type']) && $_POST['request_type'] == 'verify') {
	
	$result = $redis->get('avocado:u:request:state:'.$_POST['state']);
	
	if(!$result) {
		header('HTTP/1.1 400 Bad Request');
		die('not found');
	}
	
	$request = json_decode($result, true);
	
	$params = ['target_url', 'me', 'scope', 'state'];

	foreach($params as $p) {
		if($request[$p] != $_POST[$p]) {
			header('HTTP/1.1 400 Bad Request');
			die('go away hacker');
		}
	}
	
	die('ok');
}




if(isset($_POST['grant_type']) && $_POST['grant_type'] == 'token') {

	# Accepting the token via a POST from the remote token endpoint

	$result = $redis->get('avocado:u:request:state:'.$_POST['state']);
	if(!$result) {
		die('state disappeared');
	}

	$request = json_decode($result, true);

	$redis->set('avocado:u:request:'.$request['request_id'], json_encode($_POST));
	
	$redis->del('avocado:u:request:state:'.$_POST['state']);

	die('got it');
}


# Client polling

if(isset($_POST['grant_type']) && $_POST['grant_type'] == 'request_id') {
	
	$request = $redis->get('avocado:u:request:'.$_POST['request_id']);
	
	if(!$request) {
		header('HTTP/1.1 400 Bad Request');
		echo json_encode([
			'error' => 'bad_request',
		]);
		die();
	}
	
	if($request == 'pending') {
		header('HTTP/1.1 400 Bad Request');
		echo json_encode([
			'error' => 'authorization_pending',
		]);
		die();
	}
	
	$token = json_decode($request, true);
	
	$token_response = $token;
	unset($token_response['grant_type']);
	unset($token_response['state']);
	echo json_encode($token_response);
	die();
	
}


