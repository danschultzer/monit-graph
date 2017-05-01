<?php
$config = \MonitGraph\Base::config();

$default_config = [
  // Renderer settings
  'renderer' => [
    'template_path' => __DIR__ . '/templates/',
  ],

  // Monolog settings
  'logger' => [
    'name' => 'monit-graph',
    'path' => __DIR__ . '/../logs/monit-graph.log',
    'level' => \Monolog\Logger::DEBUG,
  ],
];

return ['settings' => array_merge($default_config, $config['slimconfig'])];
