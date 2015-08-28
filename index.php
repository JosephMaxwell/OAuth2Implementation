<?php

require 'vendor/autoload.php';
require 'models/OAuthConfig.php';
require 'models/Files.php';
require 'helpers/App.php';


$app = new \Slim\Slim([]);


$app->get('/app/auth', function() use ($app) {
    $config = new Models\OAuthConfig();

    $responseUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/app/callback';

    /**
     * url: client_secrets.json: auth_uri
     * url parameters: response_type, redirect_uri, approval_prompt=force, scope,
     *                 access_type=online
     */

    $url = '';

    $app->redirect($url);
});



$app->get('/app/callback', function() use ($app) {
    if ($code = $app->request->params('code')) {
        $config = new \Models\OAuthConfig();
        $client = new \GuzzleHttp\Client();

        /*
         * Access token exchange:
         * url: posted to client_secrets.json: token_uri
         * url parameters: code, grant_type=authorization_code, client_id, client_secret, redirect_uri
         */
        $json = array();

        Helpers\App::setAccessToken($json);

        $app->redirect('/app');
    } else if ($error = $app->request->params('error')) {
        echo $error;
    }
});



$app->get('/app', function() use ($app) {
    if (Helpers\App::isAuthorized()) {
        $cookie = Helpers\App::getCookie();
        $client = new \GuzzleHttp\Client();

        $response = $client->get('https://www.googleapis.com/drive/v2/files', [
            'headers' => [
                'Authorization' => 'Bearer ' . $cookie['access_token'],
                'Referer' => Helpers\App::getUrl()
            ]
        ]);

        $files = new Models\Files($response->getBody());
        $app->render('files.phtml', ['files' => $files->formatData()]);
    }
});




$app->get('/app/logout', function() use ($app) {
    $app->deleteCookie(Helpers\App::COOKIE);
    $app->redirect('/app/auth');
});

$app->run();