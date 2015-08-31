<?php

require 'vendor/autoload.php';
require 'models/OAuthConfig.php';
require 'models/Files.php';
require 'helpers/App.php';


$app = new \Slim\Slim([]);


$app->get('/app/auth', function() use ($app) {
    $config = new Models\OAuthConfig();

    $responseUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/app/callback';

    $url = $config->getAuthUri() . '?' .
        'response_type=code&' .
        'redirect_uri=' . urlencode($responseUrl) . '&' .
        'approval_prompt=force' .
        '&scope=' . urlencode(Helpers\App::SCOPE) . '&' .
        'client_id=' . urlencode($config->getClientId()) . '&' .
        'access_type=online&' .
        'include_granted_scopes=true';

    $app->redirect($url);
});



$app->get('/app/callback', function() use ($app) {
    if ($code = $app->request->params('code')) {
        $config = new \Models\OAuthConfig();
        $client = new \GuzzleHttp\Client();

        $params = [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => $config->getClientId(),
            'client_secret' => $config->getClientSecret(),
            'redirect_uri' => 'https://' . $_SERVER['HTTP_HOST'] . '/app/callback'
        ];

        $response = $client->post($config->getTokenUri(), [
            'form_params' => $params
        ]);

        $json = json_decode($response->getBody(), true);

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
