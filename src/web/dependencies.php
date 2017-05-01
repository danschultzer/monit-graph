<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $config = \MonitGraph\Base::config();
    $settings = $c->get('settings')['renderer'];
    $renderer = new Slim\Views\PhpRenderer($settings['template_path']);
    $renderer->addAttribute('router', $c->get('router'));
    $renderer->addAttribute('config', $config);
    return $renderer;
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};
