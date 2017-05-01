<?php
require_once __DIR__ . '/../src/monit_graph.class.php';

// Routes
$app->get('/server/{server_id}/data', function ($request, $response, $args) {
    $params = $request->getQueryParams();
    if (!isset($params['file'])) {
        return $response->withJson(array());
    }
    if (!isset($params['time_range'])) {
        $params['time_range'] = 0;
    }

    $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
    $body->write(MonitGraph::returnGoogleGraphJSON($params['file'], $params['time_range'], $this->renderer->getAttribute('config')['limit_records_shown']));

    $response = $response->withBody($body);
    return $response->withHeader('Content-Type', 'application/json;charset=utf-8');
})->setName('get_data');

$app->delete('/server/{server_id}', function ($request, $response, $args) {
    $config = $this->renderer->getAttribute('config');
    foreach ($config['server_configs'] as $server_config) {
        if ($server_config['server_id'] == $args['server_id']) {
            $name = $server_config['name'];
            break;
        }
    }

    if ($args['delete_data']=="1") {
        $filename = null;
    } else {
        $filename = $args['delete_data'];
    }
    if (MonitGraph::deleteDataFiles($args['server_id'], $filename)) {
        $yield = '<h1>All the files have been deleted successfully at ' . $name . '</h1>';
    } else {
        $yield = '<h1>Some errors happened in the deletion process';
    }

    return $this->renderer->render($response, 'delete.phtml', ['yield' => $yield]);
})->setName('confirm_delete_data');

$app->get('/server/{server_id}/delete', function ($request, $response, $args) {
    $params = $request->getQueryParams();
    return $this->renderer->render($response, '_confirm_delete.phtml', ['yield' => $yield, 'server_id' => $args['server_id'], 'delete_data' => $params['delete_data']]);
})->setName('delete_data');

$app->get('/server/{server_id}', function ($request, $response, $args) {
    $params = $request->getQueryParams();
    return $this->renderer->render($response,
                                 'server.phtml',
                                 [
                                   'server_id' => $args['server_id'],
                                   'chart_type' => @$params['chart_type'],
                                   'time_range' => @$params['time_range'],
                                   'refresh_seconds' => @$params['refresh_seconds'],
                                   'specific_services' => @$params['specific_services'],
                                   'dont_show_alerts' => @$params['dont_show_alerts']
                                 ]);
})->setName('server');

$app->get('/', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index.phtml');
})->setName('root');
