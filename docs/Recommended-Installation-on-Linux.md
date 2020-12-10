# Installation On Linux With Nginx Webserver

This assumes you've already followed the guide to install a player and the RompЯ files.

### Install some packages

    sudo apt-get install nginx php-curl php-sqlite3 php-gd php-json php-xml php-mbstring php-fpm php-intl imagemagick


### Create nginx configuration

Nginx comes set up with a default web site, which we don't want to use. You used to be able to just delete it but now we can't do that as it causes errors. So first we will edit the existing default config, since we don't want it to be the default

    sudo nano /etc/nginx/sites-available/default

Find the lines

    listen 80 default_server;
    listen [::]:80 default_server;

and change them to

    listen 80;
    listen [::]:80;

Then we will create the rompr config and set that to be the default

    sudo nano /etc/nginx/sites-available/rompr

Paste in the following lines, remembering to edit the following:

/home/YOU/web etc appropriately

server_name will be the hostname you configured on your computer, eg raspberrypi.local.

Also there is a version number in there : php7.1-fpm.sock - the 7.1 will change depending on the version of PHP installed on your system. Look in /var/run and see what's there. Note that /var/run is correct for Debian-derived distributions like Raspbian, Ubuntu, etc. It probably isn't right for some of the others but I just don't have the time.... The default nginx configuration should have a similar line that directs you where to look.

    server {

        listen 80 default_server;
        listen [::]:80 default_server;

        root /home/YOU/web;
        index index.php index.html index.htm;

        server_name hostname.of.your.computer;

        client_max_body_size 256M;

        # This section can be copied into an existing default setup
        location /rompr/ {
            allow all;
            index index.php;
            location ~ \.php {
                    try_files $uri index.php =404;
                    fastcgi_pass unix:/var/run/php/php7.1-fpm.sock;
                    fastcgi_index index.php;
                    fastcgi_param SCRIPT_FILENAME $request_filename;
                    include /etc/nginx/fastcgi_params;
                    fastcgi_read_timeout 1800;
            }
            error_page 404 = /404.php;
            try_files $uri $uri/ =404;
            location ~ /albumart/* {
                    expires -1s;
            }
        }
    }

Save the file (Ctrl-X in nano, then answer 'Y'). Now link the configuration so it is enabled

    sudo ln -s /etc/nginx/sites-available/rompr /etc/nginx/sites-enabled/rompr


If you want to host more websites on your computer, you can add further 'location' sections under the rompr section.

### Edit PHP configuration

We need to edit the PHP configuration file. Again, note that there's a version number in this path which you'll need to make sure is correct

    sudo nano /etc/php/7.1/fpm/php.ini

Now find and modify (or add in if they're not there) the following parameters. Ctrl-W is 'find' in nano.

    allow_url_fopen = On
    memory_limit = 128M
    max_execution_time = 1800
    post_max_size = 256M
    upload_max_filesize = 10M
    max_file_uploads = 200

(The last 3 entries are really only used when uploading [Custom Background Images](/RompR/Theming). They set, respectively, the maximum size of an individual file (in megabytes), the maximum number of files you can upload in one go, and the maximum total size (megabytes) you can upload in one go. The values above are just examples - but note that post_max_size has an equivalent called 'client_max_body_size' in the nginx config file and it's sensible to keep them the same).

### That's all the configuring. Let's get everything running

    sudo systemctl restart php7.1-fpm
    sudo systemctl restart nginx

That should be it. Direct your browser to http://hostname.of.your.computer/rompr (or http://ip.address.of.your.computer/rompr) and all should be well.
