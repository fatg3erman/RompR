# How to install on Linux with Apache and MySQL

RompЯ is a client for mpd or mopidy - you use RompЯ in a web browser to make mpd or mopidy play music
These are basic installation instructions for RompЯ on Linux, using the code you can download from here on github.

## Install MPD or Mopidy

Mpd should be available from your normal package manager. If you want to run Mopidy it is easy to install -  see [mopdy.com](http://www.mopidy.com).


### Player Connection Timeout

There is one thing you should adjust in the configuration for MPD and Mopidy

MPD and Mopidy both have a connection timeout parameter, after which time they will drop the connection between them and Rompr. This is seriously bad news for Rompr. You should make sure you increase it.

### For Mopidy

In mopidy.conf, your mpd section needs to contain

    [mpd]
    connection_timeout = 120

### For MPD

Somewhere in mpd.conf

    connection_timeout     "120"


If you have a very large music collection, the higher the numbeer the better. It is in seconds.

## Getting Started

This guide came about because I was installing RompЯ on a Mythbuntu 16.04 installation where I also wanted to use Mythweb. Because Mythweb brings in Apache2 I was unable to use nginx as the webserver. So I came up with this method. If you already have a mythtv install, or another system which already uses Apache2 (and mysql, optionally) then this method should work for you.

_You can use Apache with SQLite instead of MySQL if you would prefer. Ignore the steps here about setting up the MySQL server and make sure you install php7.0-sqlite instead of php7.0-mysql_

If you are using Mythweb, you should make sure you've installed it with the option to use it alongside other websites. If you want to check this, do

    sudo dpkg-reconfigure mythweb

### Install RompЯ

Download the latest release from [The Github Releases Page](https://github.com/fatg3erman/RompR/releases)

Let's assume you extracted the zip file into a folder called 'web' in your home directory. So now you have /home/YOU/web/rompr. From now on we're going to refer to that as /PATH/TO/ROMPR, because that's what programmers do and it makes the guide more general. You can put the code anywhere you like, although it won't work very well if you put it in the oven. So you'll need to look out for /PATH/TO/ROMPR in everything below and make sure you substitute the correct path.

### Set directory permissions

We need to create directories to store data in.

    cd /PATH/TO/ROMPR
    mkdir prefs
    mkdir albumart


And then we need to give Apache permission to write to them. We can do this by changing the ownership of those directories to be the user that Apache runs as. This may differ depending on which distro you're running, but this is good for all Ubuntus, where Apache runs as the user www-data.

    sudo chown www-data /PATH/TO/ROMPR/albumart
    sudo chown www-data /PATH/TO/ROMPR/prefs


### Make sure Apache can find RompЯ

We need to make sure Apache can find the stuff you've just downloaded. To do this we create a soft link from Apache's base directory to the folder you downloaded. On Ubuntu, the default base directory for Apache is /var/www/html, so

    sudo ln -s /PATH/TO/ROMPR /var/www/html/rompr


### Install some packages

    sudo apt-get install apache2 php-curl php-mysql php-gd php-json php-xml php-mbstring imagemagick


### Enable some Apache modules

    sudo a2enmod expires
    sudo a2enmod headers
    sudo a2enmod deflate
    sudo a2enmod php7.0


### Create Apache configuration

We're going to create an Apache configuration file for RompЯ. I'll assume it's been placed in my home directory, eg at /home/YOU/web/rompr.conf. We'll refer to that as /PATH/TO/ROMPRCONF.

So, create this file, note I've assumed the default apache root directory of /var/www/html

    Timeout 1800

    <Directory /var/www/html/rompr>
        Options Indexes FollowSymLinks MultiViews Includes ExecCGI
        DirectoryIndex index.php
        AllowOverride All
        AddType image/x-icon .ico
        Order Allow,Deny
        Allow from All
        Require all granted

        <IfModule mod_php7.c>
            AddType application/x-httpd-php .php
            php_flag magic_quotes_gpc Off
            php_flag track_vars On
            php_admin_flag allow_url_fopen On
            php_value include_path .
            php_admin_value upload_tmp_dir /var/www/html/rompr/prefs/temp
            php_admin_value open_basedir none
            php_admin_value memory_limit 128M
            php_admin_value post_max_size 256M
            php_admin_value upload_max_filesize 32M
            php_admin_value max_file_uploads 50                
            php_admin_value max_execution_time 1800         
        </IfModule>

    </Directory>

    <Directory /var/www/html/rompr/albumart/small>
        Header set Cache-Control "no-cache, no-store, must-revalidate"
        Header set Pragma "no-cache"
        Header set Expires 0
    </Directory>

    <Directory /var/www/html/rompr/albumart/medium>
        Header set Cache-Control "no-cache, no-store, must-revalidate"
        Header set Pragma "no-cache"
        Header set Expires 0
    </Directory>

    <Directory /var/www/html/rompr/albumart/asdownloaded>
        Header set Cache-Control "no-cache, no-store, must-revalidate"
        Header set Pragma "no-cache"
        Header set Expires 0
    </Directory>

Now symlink that file so that Apache can find it

    sudo ln -s /PATH/TO/ROMPRCONF /etc/apache2/sites-enabled/rompr.conf

### Create mysql database

Now we'll create the mysql database for RompЯ. You need to read [Using a MySQL Database](/RompR/Using-a-MySQL-server)

### Finally....

Restart all the system services we've changed

    sudo service apache2 restart
    sudo service mysql restart

### And Tell RompЯ to use MySQL

Visit your new RompЯ installation at http://ip.of.computer.with.rompr/rompr?setup
On that page, configure RompЯ to use MySQL and set usernames, passwords, ports, etc as appropriate (the defaults should work unless you've changed anything in your mysql install). Click 'OK' at the bottom.

To access RompЯ normally, just use http://ip.of.computer.with.rompr/rompr
