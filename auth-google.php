<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require 'vendor/autoload.php';

require_once 'google-util.php';

$config = parse_ini_file('config.ini', true);

$client = createGoogleClient(
    $config['google']['google_api_credentials_file'],
    $config['google']['google_api_oauthcallback_uri']);

if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
    $client->authenticate($_GET['code']);
    $_SESSION['access_token'] = $client->getAccessToken();
    $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/medlemsregister/';
    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}
?>