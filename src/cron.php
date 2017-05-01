<?php
    require_once __DIR__ . '/../src/monit_graph.class.php';

    $config = MonitGraph::config();
  if (!MonitGraph::checkConfig($config['server_configs'])) {
      die();
  }

  /* Running each instance of the config */
  foreach ($config['server_configs'] as $server_config) {
      MonitGraph::cron($server_config['server_id'],
                     $server_config['config']['url'],
                               $server_config['config']['uri_xml'],
                               $server_config['config']['url_ssl'],
                               $server_config['config']['http_username'],
                               $server_config['config']['http_password'],
                               $server_config['config']['verify_ssl'],
                               $config['chunk_size'],
                               $config['limit_number_of_chunks']);
  }
