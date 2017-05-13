<?php
class TrumpTrackerBot {
	public $redditclient;

	public function __construct($clientId,$clientSecret) {
		require("OAuth2/Client.php"); //https://github.com/adoy/PHP-OAuth2
		require("OAuth2/GrantType/IGrantType.php"); //https://github.com/adoy/PHP-OAuth2
		require("OAuth2/GrantType/AuthorizationCode.php"); //https://github.com/adoy/PHP-OAuth2
		$this->redditclient = new OAuth2\Client($clientId, $clientSecret, OAuth2\Client::AUTH_TYPE_AUTHORIZATION_BASIC);
		$this->redditclient->setCurlOption(CURLOPT_USERAGENT,"TrumpTrackerBot/1.0 for /r/TrumpTracker by /u/Luit03");
	}
	public function getData() { //This function gets the latest data.json from GitHub
		return json_decode(file_get_contents("https://raw.githubusercontent.com/TrumpTracker/trumptracker.github.io/master/_data/data.json"),true);
	}
	public function oAuthLogin() { //Prompt the user to give us access to his reddit account (/u/TrumpTracker)
		$state = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
		$authUrl = $this->redditclient->getAuthenticationUrl("https://ssl.reddit.com/api/v1/authorize", 'https://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']), array("scope" => "identity submit modposts modflair read privatemessages edit", "state" => $state));
		header("Location: ".$authUrl);
		die("Redirect");
	}
	public function connectToReddit($code) { //We should have permission from the user, now lets connect to reddit
		$i = 0;
		$params = array("code" => $code, "redirect_uri" => 'https://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']));
		$response = $this->redditclient->getAccessToken("https://ssl.reddit.com/api/v1/access_token", "authorization_code", $params);
		$accessTokenResult = $response["result"];
		$this->redditclient->setAccessToken($accessTokenResult["access_token"]);
		$this->redditclient->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);
    }
	public function getMe() { //This gets personal information about the user's account. Just use this as a 'ping'
		return $this->redditclient->fetch("https://oauth.reddit.com/api/v1/me.json");
	}
	public function submitPost($title,$url,$subreddit) { //Submit a post to a certain subreddit.
		return $this->redditclient->fetch("https://oauth.reddit.com/api/submit?api_type=json&kind=link&sendreplies=false&sr=" . $subreddit . "&title=" . urlencode($title) . "&url=" . urlencode($url),array(),"POST");
	}
	public function submitComment($text,$parent) {
		return $this->redditclient->fetch("https://oauth.reddit.com/api/comment?api_type=json&thing_id=" . $parent . "&text=" . urlencode($text),array(),"POST");
	}
	public function distinguishItem($comment,$how,$sticky = false) {
		return $this->redditclient->fetch("https://oauth.reddit.com/api/distinguish?api_type=json&id=" . $comment . "&how=" . urlencode($how) . "&sticky=" . $sticky,array(),"POST");
	}
	public function approvePost($post) {
		return $this->redditclient->fetch("https://oauth.reddit.com/api/approve?id=" . urlencode($post),array(),"POST");
	}
	public function format($title,$url) { //Format all the information into the format
		$title = "[Trump] " . $title;
		if(strlen($title) > 300) {
			$title = substr($title,0,299).'-';
		}
		return array("title" => $title, "url" => $url);
	}
	public function exportJSON($file,$json) {
		file_put_contents($file,json_encode($json));
	}
}
