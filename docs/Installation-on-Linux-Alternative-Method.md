# How to install on Linux with Apache

This assumes you've already followed the guide to install a player and the RompЯ files.

## Getting Started

This guide came about because I was installing RompЯ on a Mythbuntu 16.04 installation where I also wanted to use Mythweb. Because Mythweb brings in Apache2 I was unable to use nginx as the webserver. So I came up with this method. If you already have a mythtv install, or another system which already uses Apache2 then this method should work for you.

If you are using Mythweb, you should make sure you've installed it with the option to use it alongside other websites. If you want to check this, do

    sudo dpkg-reconfigure mythweb

### Make sure Apache can find RompЯ

We need to make sure Apache can find RompЯ. To do this we create a soft link from Apache's base directory to the folder you downloaded. On Ubuntu, the default base directory for Apache is /var/www/html, so

    sudo ln -s /home/YOU/web/rompr /var/www/html/rompr


### Install some packages

    sudo apt-get install apache2 php-curl php-sqlite3 php-gd php-json php-xml php-mbstring imagemagick


### Enable some Apache modules

    sudo a2enmod expires
    sudo a2enmod headers
    sudo a2enmod deflate
    sudo a2enmod php7.0

Note - the 7.0 may need to change depending on your PHP version

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

### Finally....

Restart all the system services we've changed

    sudo systemctl restart apache2

To access RompЯ, just use http://ip.of.computer.with.rompr/rompr
