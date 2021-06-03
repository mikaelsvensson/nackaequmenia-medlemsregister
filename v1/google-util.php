<?php
function createGoogleClient($authConfigFile, $redirectUri)
{
    // create the Google client
    $client = new Google_Client();

    $client->setAuthConfig($authConfigFile);
    $client->setRedirectUri($redirectUri);
//    $client->addScope(Google_Service_Plus::USERINFO_EMAIL);
    $client->addScope("http://www.google.com/m8/feeds/");

    // returns a Guzzle HTTP Client
    return $client;
}

?>