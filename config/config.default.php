<?php
  if (!file_exists(__DIR__ . "/servers.ini")) {
      die("Please set config/servers.ini first");
  }

  return [
    /* Monit-Graph display information */
    'default_time_range' => 3600, // Amount in seconds of the default view should be (0 equals all available data)
    'default_chart_type' => "LineChart", // Default chart type
    'default_refresh_seconds' => 120, // Default amount of seconds before data is reloaded (0 equals never)
    'default_specific_service' => "", // Default service to be displayed (none is equal to all services)
    'default_dont_show_alerts' => "on",
    'limit_records_shown' => 750,

    /* Monit-Graph history handling */
    'chunk_size' => 1024*1024, // Maximum size in bytes for each service history chunk (0 equals unlimited, remember to set php.ini so the scripts can handle it as well)
    'limit_number_of_chunks' => 14, // Maximum number of chunks saved per service records, will delete all above this (0 equals unlimited)

    'server_configs' => parse_ini_file(__DIR__ . "/servers.ini", true),

    'slimconfig' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
    ],

    // Basic authentication can be enabled. By default, ADMIN_PASSWORD environment variables
    // is used. You can generate your own hashed password by running `htpasswd -nbBC 10 username password`
    // 'basic_auth_users' => ['admin' => getenv('ADMIN_PASSWORD')]
  ];
