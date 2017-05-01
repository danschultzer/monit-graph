# Monit Graph

Monit Graph is a logging and graphing tool for Monit written in PHP5. It can manage big amounts of data, and will keep a history of Monit statuses.

![Monit Graph Detail Panel](https://dreamconception.com/images/2012/06/monit-graph-detail1.png)

#### Features

* Easy to manage and customize
* Several different graphs (Google Charts) of memory, cpu, swap and alert activity
* Data logging with XML files
* Chunk rotation and size limitation
* Multiple server setup

## Get started

To get started, you will first need to have Monit installed with HTTP access enabled. You can read more under "Setting up Monit".

1. `composer install`
2. Add `config/servers.ini` (you can use [`servers.template.ini`](config/servers.template.ini))
3. Set up a crontab job to run cron every minute:
   ```cron
   * * * * * cd /path/to/monit-graph && composer cron >> /var/log/monit-graph.log
   ```
4. Start server:
   ```bash
   composer server
   ```

## Setting up Monit

To setup Monit on Ubuntu, please follow the below steps.

#### Install Monit

```bash
sudo apt-get update
sudo apt-get install monit
```

#### Edit configuration file for Monit

```bash
sudo vi /etc/monit/monitrc
```

Make sure the following parameters are set correctly (these are examples, adjust accordingly):

```conf
set idfile /var/run/monit-id
set statefile /var/run/monit-state
set daemon 60
set logfile /var/log/monit.log

set mailserver localhost
set mail-format { from: monit@mydomain.com }

set alert myemail@myemaildomain.com	                 # receive all alerts
set httpd port 2812 and use the address XX.XX.XX.XX  # Remove "and use the address XX.XX.XX.XX", if not bind to specific IP
  ssl enable                                         # Enabling SSL
  pemfile /etc/ssl/monit.pem                         # The PEM file
  signature disable                                  # No server signature to send
  allow mylogin:"mypassword"                         # Login
```

Remember to allow httpd to run, or else Monit graph cannot contact you.

#### Add services to Monit

Add a few configuration files into the /etc/monit/conf.d/ directory. You can use the examples from the monitrc directory.

Check if the configuration are good:

```bash
monit -t
```

#### Restart Monit with the new configuration

Restart

```bash
service monit restart
```

## Tips

1. If the script have trouble managing big amounts of data, try increase the allowed allocated memory in a .htaccess

2. REMEMBER to password protect the directory with .htaccess or anything appropriate

3. Loading many services can be very heavy for your browser, try specify the services you wish be shown.

## Contributing

Monit-Graph has a few tools to help development.

### Build massive data structure

```bash
composer build-massive-data
```

## Links
[Blog post about Monit and Monit-Graph](https://dreamconception.com/tech/tools/measure-your-server-performance-with-monit-and-monit-graph/)

[Official Monit Website](http://mmonit.com/monit/)

## About
Dan Schultzer works at Dream Conception (http://dreamconception.com/). This script was developed to increase the usability of Monit.
