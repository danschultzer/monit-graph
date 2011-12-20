#Monit Graph

Monit Graph is a logging and graphing tool for Monit written in PHP5. It can manage big amounts of data, and will keep a history of Monit statuses.

####Features
* Easy to manage and customize
* Several different graphs (Google Charts) of memory, cpu, swap and alert activity
* Data logging with XML files
* Chunk rotation and size limitation
* Multiple server setup


##Get started

To get started, you will first need to have Monit installed and enabled with HTTP access. You can read more under "Setting up Monit".

1. Change permissions for data directory to 777.
2. Change permissions for data/index.php to 644.
3. Modify config.php to match your setup of monit as well as needs of graphing.
4. Setup a crontab job to run cron.php every minute. Example:
    \* * * * * php /path/to/monit/cron.php >>/var/log/monit-graph.log
5. Verify after a few minutes of running that the logging happens. You can check the php error log if there seams to be something wrong.


##Setting up Monit

To setup Monit on Ubuntu, please follow the below steps.

#### Install Monit

    # sudo apt-get update
    # sudo apt-get install monit

#### Edit configuration file for Monit

    # sudo vi /etc/monit/monitrc

Make sure the following parameters are set correctly (these are examples, adjust accordingly):

    set idfile /var/run/monit-id
    set statefile /var/run/monit-state
    set daemon 60
    set logfile /var/log/monit.log

    set mailserver localhost
    set mail-format { from: monit@mydomain.com }

    set alert myemail@myemaildomain.com								# receive all alerts
    set httpd port 2812 and use the address XX.XX.XX.XX			# Remove "and use the address XX.XX.XX.XX", if not bind to specific IP
    	ssl enable																			# Enabling SSL
    	pemfile /etc/ssl/monit.pem												# The PEM file
    	signature disable																# No server signature to send
    	allow mylogin:"mypassword"												# Login

Remember to allow httpd to run, or else Monit graph cannot contact you.

#### Add services to Monit

Add a few configuration files into the /etc/monit/conf.d/ directory. You can use the examples from the monitrc directory.

Check if the configuration are good:

    # monit -t

#### Restart Monit with the new configuration

Restart

    # service monit restart


##Tips

1. If the script have trouble managing big amounts of data, try increase the allowed allocated memory in a .htaccess

2. REMEMBER to password protect the directory with .htaccess or anything appropriate

3. Loading many services can be very heavy for your browser, try specify the services you wish be shown.

4. For added security, remove the tools directory

##Links
http://mmonit.com/monit/


##About
Dan Schultzer works at Dream Conception (http://dreamconception.com/) and Abcel (http://abcel-online.com). This script was developed to increase the usability of Monit.