<?php
/**
 * Google Plus (Google+) Class
 *
 * Example configuration array passed to constructor:
 *
 *    $config['consumer_key'] = '123456789.apps.googleusercontent.com';
 *    $config['consumer_secret']   = 'ABCdefhi_JKLmnop';
 *    $config['callbackUrl']  = 'http://' . $_SERVER['SERVER_NAME'] . '/googleplus/verify/';
 *
 *     $GooglePlus = new GooglePlusPHP($config);
 */
class GooglePlusPHP {

	public $consumerKey;
	public $consumerSecret;
	public $oauthToken;
	public $oauthTokenSecret;
	public $callbackUrl;

	protected $authToken;

	/**
	 * Class Constructor
	 *
	 * @param array $config 
	 */
	function __construct($config) {
		$this->consumerKey = $config['consumer_key'];
		$this->consumerSecret = $config['consumer_secret'];
		$this->callbackUrl = $config['callbackUrl'];

		/* Set Up OAuth Consumer */
		if (isset($config['oauth_token']) && $config['oauth_token_secret']):
			$this->oauthToken = $config['oauth_token'];
			$this->oauthTokenSecret = $config['oauth_token_secret'];
		endif;
	}

	/**
	 * Get Authorization Url
	 *
	 * @param string $callbackUrl 
	 * @return $url
	 */
	function getAuthorizationUrl($callbackUrl = null) {

		/* Override if needed, else assume it was set at __construct() */
		if ($callbackUrl)
			$this->callbackUrl = $callbackUrl;

		/* Authorization URL */
		$url = sprintf('https://accounts.google.com/o/oauth2/auth?redirect_uri=%s&response_type=code&client_id=%s&approval_prompt=force&scope=%s&access_type=offline',
			urlencode($this->callbackUrl),
			$this->consumerKey,
			urlencode('https://www.googleapis.com/auth/plus.me')
		);

		return $url;
	}

	/**
	 * Get Access Token
	 *
	 * @param string $code 
	 * @param string $isRefresh 
	 * @return $response
	 */
	function getAccessToken($code = null, $isRefresh = false) {

		$data = array();
		if (!$isRefresh):
			$data['code'] = $code;
			$data['client_id'] = $this->consumerKey;
			$data['client_secret'] = $this->consumerSecret;
			$data['redirect_uri'] = $this->callbackUrl;
			$data['grant_type'] = 'authorization_code';
			$data['scope'] = null;
		else:
			$data['client_id'] = $this->consumerKey;
			$data['client_secret'] = $this->consumerSecret;
			$data['refresh_token'] = $code;
			$data['grant_type'] = 'refresh_token';
		endif;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://accounts.google.com/o/oauth2/token');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);

		if (in_array(curl_getinfo($ch, CURLINFO_HTTP_CODE), array(400,401)) ):
			$t_response = json_decode($response);
			if ($t_response->error != '')
				$response = $t_response->error;
			throw new GooglePlusPHPException('Server: ' . $response, curl_getinfo($ch, CURLINFO_HTTP_CODE));
		endif;

		$response = json_decode($response);
		return $response;

	}

	/**
	 * Set OAuth token
	 *
	 * @param string $token 
	 */
	function setOAuthToken($token = null) {
		$this->oauthToken = $token;
	}


	/* Helpers */

	/**
	 * Test Authentication
	 *
	 * @return boolean
	 */
	function testAuth() {
		$url = 'https://www.googleapis.com/plus/v1/people/me';
		try {
			$result = $this->request($url);
			return true;
		} catch (GooglePlusPHPException $e) {
			return false;
		}
	}

	/**
	 * Get My Profile
	 *
	 * @return $result
	 */
	function getMyProfile() {
		$url = 'https://www.googleapis.com/plus/v1/people/me';
		$result = $this->request($url);
		return $result;
	}

	/**
	 * Get User Profile by ID
	 *
	 * @param string $profile_id 
	 * @return $result
	 */
	function getUserProfile($profile_id = 0) {
		if (!is_numeric($profile_id))
			return false;

		$url = sprintf('https://www.googleapis.com/plus/v1/people/%s', $profile_id);
		$result = $this->request($url);
		return $result;
	}

	/**
	 * Get My Activities
	 *
	 * @param string $pageToken 
	 * @return $result
	 */
	function getMyActivities($pageToken = null) {
		$url = 'https://www.googleapis.com/plus/v1/people/me/activities/public';
		$result = $this->request($url, array('pageToken' => $pageToken) );
		return $result;
	}

	/**
	 * Get Public Activites
	 *
	 * @param string $profile_id 
	 * @param string $pageToken 
	 * @return $result
	 */
	function getPublicActivities($profile_id = 0, $pageToken = null) {
		if (!is_numeric($profile_id))
			return false;

		$url = sprintf('https://www.googleapis.com/plus/v1/people/%s/activities/public', $profile_id);
		$result = $this->request($url, array('pageToken' => $pageToken) );
		return $result;
	}

	/**
	 * Search People
	 *
	 * @param string $query 
	 * @param string $pageToken 
	 * @return $result
	 */
	function searchPeople($query = null, $pageToken = null) {
		if (empty($query))
			return false;

		$url = 'https://www.googleapis.com/plus/v1/people';
		$result = $this->request($url, array('query' => urlencode($query), 'pageToken' => $pageToken) );

		return $result;
	}

	function getActivityReplies($activity_feed = '') {
		$url = $activity_feed;
		$result = $this->request($url);
		return $result;
	}


	/* Private request method */

	/**
	 * Request Resource
	 *
	 * @param string $url 
	 * @param array $data 
	 * @return $return
	 */
	private function request($url, $data = array()) {

		$headers = array();
		$headers[] = "Authorization: OAuth " . $this->oauthToken;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data) );
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);

		// Throw exception on no response from server
		if (!$output)
			throw new GooglePlusPHPException('No response from server.', curl_getinfo($ch, CURLINFO_HTTP_CODE) );

		$return = json_decode($output);
		
		// Throw an exception on error
		if (isset($return->error))
			throw new GooglePlusPHPException($return->error->message, $return->error->code);
		
		return $return;
	}

}

/**
 * Google Plus (Google+) Exception Class
 */
class GooglePlusPHPException extends Exception {
	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}