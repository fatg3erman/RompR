# Installation Guide
RompЯ is a client for mpd or mopidy - you use RompЯ in a web browser to make mpd or mopidy play music
These are basic installation instructions for RompЯ on Linux, using the code you can download from here on github.

_Please be aware that I generally just use the master branch and it may be very unstable._

**If you want to download a stable release you should visit the project homepage which for want of more time to do something better is at [SourceForge](https://sourceforge.net/projects/rompr/). There you will find a fuller wiki, a discussion forum, and installation instructions for Linux and macOS.**

## Assumptions
I'm going to assume you already have mpd or mopidy installed and working. This is not the place to discuss the arcane art of configuring mpd. For that you'll have to read the mpd community wiki. Sorry about that. The mopidy instructions are quite good.

This guide works on the assumption that you're using RompЯ on a machine that has apache2 and mysql installed and set up already. If you've installed Mythbuntu and Mythweb then this will be the csae.

## How to install on Linux with Apache and MySQL
This guide came about because I was installing RompЯ on a Mythbuntu 16.04 installation where I also wanted to use Mythweb. Because Mythweb brings in Apache2 I was unable to use nginx as the webserver. So I came up with this method. If you already have a mythtv install, or another system which already used Apache2 (and mysql, optionally) then this method should work for you.

If you are using Mythweb, you should make sure you've installed it with the option to use it alongside other websites. If you want to check this, do

    sudo dpkg-reconfigure mythweb

### Install RompЯ
Download a zip file from the big green button that says 'Download .zip'. When you extract the zip file you'll get a directory with a wierd name. The contents of that directory are what you want.

Let's assume you extracted the zip file into your home directory, in a fiolder called 'web'. You'll now have a folder called /home/YOU/web/fatg3rman-RompR-8e47b94, or something along those lines. The first thing to do, for the same of simplicity, is to rename it to 'rompr'. So now you have /home/YOU/web/rompr. From now on we're going to refer to that as /PATH/TO/ROMPR, because that's what programmers do and it makes the guide more general. You can put the code anywhere you like, although it won't work very well if you put it in the oven. So you'll need to look out for /PATH/TO/ROMPR in everything below and make sure you substitute the correct path.

### Set directory permissions
We need to create directories to store data in.

    cd /PATH/TO/ROMPR
    mkdir prefs
    mkdir albumart
    mkdir albumart/small
    mkdir albumart/asdownloaded


And then we need to give Apache permission to write to them. We can do this by changing the ownership of those directories to be the user that Apache runs as. This may differ depending on which distro you're running, but this is good for all Ubuntus, where Apache runs as the user www-data.

    sudo chown -R www-data /PATH/TO/ROMPR/albumart
    sudo chown -R www-data /PATH/TO/ROMPR/prefs


### Make sure Apache can find RompЯ
We need to make sure Apache can find the stuff you've just downloaded. To do this we create a soft link from Apache's base directory to the folder you downloaded. On Ubuntu, the default base directory for Apache is /var/www/html, so

    sudo ln -s /PATH/TO/ROMPR /var/www/html/rompr


### Install some packages

    sudo apt-get install php7.0-mysql nginx php7.0-curl imagemagick php7.0-json php7.0-xml php7.0-mbstring

_Note the version numbers - 7.0 is current for Ubuntu 16.04 at the time of  writing but as times change it may become 7.1, etc. Amend the command as applicable_

_If you want to use SQLite instead of mysql, substitute php7.0-sqlite3 for php7.0-mysql_


### Enable some Apache modules

    sudo a2enmod expires
    sudo a2enmod headers
    sudo a2enmod deflate
    sudo a2enmod php7.0


### Create Apache configuration
We're going to create an Apache configuration file for RompЯ. I'll assume it's been placed in my home directory, eg at /home/YOU/web/rompr.conf. We'll refer to that as /PATH/TO/ROMPRCONF.

So, create this file, note I've assumed the default apache root directory of /var/www/html

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
            php_admin_value upload_tmp_dir /var/www/html/rompr/prefs
            php_admin_value open_basedir none
            php_admin_value memory_limit 128M
        </IfModule>

    </Directory>

    <Directory /var/www/html/rompr/albumart/small>
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

`sudo ln -s /PATH/TO/ROMPRCONF /etc/apache2/sites-enabled/rompr.conf`

### Create mysql database
Now we'll create the mysql database for RompЯ. You will need to know your mysql root password. If you've installed the standard mythbuntu install, this will be your login password.

    mysql -uroot -p
    CREATE DATABASE romprdb CHARACTER SET utf8 COLLATE utf8_unicode_ci;
    USE romprdb;
    GRANT ALL ON romprdb.* TO rompr@localhost IDENTIFIED BY 'romprdbpass';
    FLUSH PRIVILEGES;
    quit;

Those commands set up the RompЯ database using a default username and password. Note that any usernames and passwords you put in will be stored by RompЯ in plain text, so don't use anything important.

We also want to set some configuration values for mysql to increase performance. Create another file somewhere, like we did for the Apache configuration file, called rompr-tweaks.cnf (note it MUST end in .cnf or it will be ignored). Put the following in it

    [mysqld]  
    query_cache_limit       = 16M
    query_cache_size        = 64M
    innodb_buffer_pool_size = 64M
    innodb_flush_log_at_trx_commit = 0

And now link this file so mysql can find it

    sudo ln -s /PATH/TO/ROMPR-TWEAKS /etc/mysql/conf.d/rompr-tweaks.cnf
    sudo ln -s /PATH/TO/ROMPR-TWEAKS /etc/mysql/mysql.conf.d/rompr-tweaks.cnf

### Finally....
Restart all the system services we've changed

    sudo service apache2 restart
    sudo service mysql restart

### And Tell RompЯ to use MySQL
Visit your new RompЯ installation at http://ip.of.computer.with.rompr/rompr?setup
On that page, configure RompЯ to use MySQL and set usernames, passwords, ports, etc as appropriate (the defaults should work unless you've changed anything in your mysql install). Click 'OK' at the bottom.

To access RompЯ normally, just use http://ip.of.computer.with.rompr/rompr

## Pages On This website

[Home](https://fatg3erman.github.io/RompR/)

[Recommended Linux Installation - with Nginx](https://fatg3erman.github.io/RompR/Recommended-Installation-on-Linux)

[Alternative Linux Installation - with Apache](https://fatg3erman.github.io/RompR/Installation-on-Linux-Alternative-Method)

[Troubleshooting](https://fatg3erman.github.io/RompR/Troubleshooting)
