<?php

/* Require library */
require_once 'class.googleplus.php';

/* Configuration Values */
$config['consumer_key'] = '123456789.apps.googleusercontent.com';
$config['consumer_secret']   = 'ABCdefhi_JKLmnop';
$config['callbackUrl']  = 'http://' . $_SERVER['SERVER_NAME'] . '?verify';

$GooglePlus = new GooglePlusPHP($config);

/* Verification phase */
if (!isset($_SESSION['googlePlusOAuth']) && isset($_GET['verify']) && isset($_GET['code'])):
	try {
		unset($_SESSION['googlePlusOAuth']);
		$accessToken = $GooglePlus->getAccessToken($_GET['code']);
		$GooglePlus->setOAuthToken($accessToken->access_token, false);
		$_SESSION['googlePlusOAuth'] = $accessToken;
	} catch (Exception $e) {
		die($e->getMessage());
		exit;
	}
	header('Location: example.php');
	exit;
endif;

/* Set Access Token */
$GooglePlus->setOAuthToken($GooglePlusToken->access_token);

if (!$GooglePlus->testAuth())
	die('Your token probably expired, or was not valid. Clear the session and try again.');
	

/* Profile */
$profile = $GooglePlus->getMyProfile();


/* My Activities */
$activities = $GooglePlus->getMyActivities();

/* People Search */
if (isset($_GET['search'])):
	if (isset($_GET['search_pagetoken'])):
		$search_pagetoken = $_GET['search_pagetoken'];
	else:
		$search_pagetoken = null;
	endif;
	$search_results = $GooglePlus->searchPeople($_GET['search'], $search_pagetoken);
endif;

/* Load Profile, override $activities */
if (isset($_GET['profile_id'])):
	$profile_id = $_GET['profile_id'];
	if (!is_numeric($profile_id)): continue; endif;
	
	$activities = $GooglePlus->getPublicActivities($profile_id);
	
	$user_profile = $GooglePlus->getUserProfile($profile_id);
endif;

?>
<html>
<head>
<title>Sample Google+</title>
</head>
<body>

<h1>Google+ Example Script</h1>

<h2>$profile</h2>
<pre><?php var_dump($profile); ?></pre>
<hr />

<h2>$search_results</h2>
<p>Set $_GET['search'] to view results.</p>
<pre><?php var_dump($search_results); ?></pre>
<hr />

<h2>$user_profile</h2>
<p>Set $_GET['profile_id'] to view results.</p>
<pre><?php var_dump($user_profile); ?></pre>
<hr />

<h2>$activities</h2>
<pre><?php var_dump($activities); ?></pre>

</body>
</html>