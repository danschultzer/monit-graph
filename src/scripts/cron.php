<?php
require __DIR__ . '/../../vendor/autoload.php';

$config = \MonitGraph\Base::config();

if (!\MonitGraph\Base::checkConfig($config['server_configs'])) {
    die();
}

  /* Running each instance of the config */
foreach ($config['server_configs'] as $server_config) {
    \MonitGraph\Base::cron(
        $server_config['server_id'],
        $server_config['config']['url'],
        $server_config['config']['uri_xml'],
        $server_config['config']['url_ssl'],
        $server_config['config']['http_username'],
        $server_config['config']['http_password'],
        $server_config['config']['verify_ssl'],
        $config['chunk_size'],
        $config['limit_number_of_chunks']
    );
}
