<?php
// Application middleware
if (isset(\MonitGraph\Base::config()['basic_auth_users'])) {
    $app->add(new \Slim\Middleware\HttpBasicAuthentication([
      "users" => \MonitGraph\Base::config()['basic_auth_users']
    ]));
}
