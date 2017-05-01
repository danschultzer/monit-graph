<?php
// Application middleware
require_once __DIR__ . '/../src/monit_graph.class.php';

if (isset(MonitGraph::config()['basic_auth_users'])) {
    $app->add(new \Slim\Middleware\HttpBasicAuthentication([
      "users" => MonitGraph::config()['basic_auth_users']
  ]));
}
