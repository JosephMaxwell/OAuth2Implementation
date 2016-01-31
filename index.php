<?php

require 'vendor/autoload.php';
require 'models/Config.php';
require 'models/Files.php';
require 'helpers/App.php';
require 'controllers/ManualController.php';
require 'controllers/LibraryController.php';


$configuration = [
    'settings' => [
        'displayErrorDetails' => true
    ]
];
$container = new \Slim\Container($configuration);
$app = new \Slim\App($container);

$container['config'] = new \Models\Config;
$container['helper'] = new \Helpers\App($container);
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('templates', [
        'cache' => false
    ]);
    $view->addExtension(new \Slim\Views\TwigExtension(
        $container['router'],
        $container['request']->getUri()
    ));

    return $view;
};

$areas = [
    'manual', 'library'
];

foreach ($areas as $area) {
    $properName = ucfirst($area);
    $app->get("/{$area}", "Controllers\\{$properName}Controller:index")
        ->setName('Homepage');

    $app->get("/{$area}/auth", "Controllers\\{$properName}Controller:authenticate")
        ->setName('Authenticate');

    $app->get("/{$area}/callback", "Controllers\\{$properName}Controller:authenticateCallback")
        ->setName('Authentication Callback');

    $app->get("/{$area}/logout", "Controllers\\{$properName}Controller:logout")
        ->setName('Logout');
}

$app->run();
