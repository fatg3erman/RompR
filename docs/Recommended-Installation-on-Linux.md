# Installation On Linux With Nginx Webserver

This assumes you've already followed the guide to install a player and the RompЯ files.

### Install some packages

**Debian / Ubuntu / Raspberry Pi OS / Mint etc**

    sudo apt-get install nginx php-curl php-json php-xml php-mbstring php-sqlite3 php-gd php-fpm php-intl imagemagick

**Arch / Manjaro etc**

    sudo pacman -S nginx php-sqlite php-gd php-fpm php-intl imagemagick libwmf libjxl

### Create nginx configuration

**On Arch / Manjaro etc**

We're going to create a directory in which to put our website configuration, instead of messing with the default one:

    sudo mkdir /etc/nginx/sites-available
    sudo mkdir /etc/nginx/sites-enabled

and then add one line to the default config to make it read the files we'll put in there

    sudo nano /etc/nginx/nginx.conf

and paste in at the bottom, before the final }

    include /etc/nginx/sites-enabled/*;

**All Distributions**

Now we will create the rompr config.

    sudo nano /etc/nginx/sites-available/rompr

Paste in the following lines, remembering to edit /home/YOU/web appropriately, and set hostname.of.your.computer to, well, the hostname of your computer

    server {

        listen 80;
        listen [::]:80;

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

                    # This line for Debian / Ubuntu
                    fastcgi_pass unix:/run/php/php-fpm.sock;
                    # This line for Arch / Manjaro
                    fastcgi_pass unix:/run/php-fpm/php-fpm.sock;

                    fastcgi_index index.php;
                    fastcgi_param SCRIPT_FILENAME $request_filename;
                    include /etc/nginx/fastcgi_params;
                    fastcgi_read_timeout 1800;
            }
            error_page 404 = /rompr/404.php;
            try_files $uri $uri/ =404;
            location ~ /albumart/* {
                    expires -1s;
            }
        }
    }

Save the file (Ctrl-X in nano, then answer 'Y'). Now link the configuration so it is enabled

    sudo ln -s /etc/nginx/sites-available/rompr /etc/nginx/sites-enabled/rompr

If you want to host more websites on your computer, you can add further 'location' sections under the rompr section, or add new files under sites-available and link them as above.

### Edit PHP configuration

We need to edit the PHP configuration file.

**Debian / Ubuntu / Raspberry Pi OS / Mint etc**

Again, note that there's a version number in this path which you'll need to make sure is correct

    sudo nano /etc/php/7.1/fpm/php.ini

**Arch / Manjaro etc**

    sudo nano /etc/php/php.ini

Now find and modify (or add in if they're not there) the following parameters. Ctrl-W is 'find' in nano.

    allow_url_fopen = On
    memory_limit = 128M
    max_execution_time = 1800
    post_max_size = 256M
    upload_max_filesize = 10M
    max_file_uploads = 200

(The last 3 entries are really only used when uploading [Custom Background Images](/RompR/Theming). They set, respectively, the maximum size of an individual file (in megabytes), the maximum number of files you can upload in one go, and the maximum total size (megabytes) you can upload in one go. The values above are just examples - but note that post_max_size has an equivalent called 'client_max_body_size' in the nginx config file and it's sensible to keep them the same).

**Arch / Manjaro etc**

You also need to find and enable (remove the ; from the start of the line) the following PHP extensions:

    extension=curl
    extension=pdo_sqlite
    extension=gd
    extension=intl

### That's all the configuring. Let's get everything running

**Debian / Ubuntu / Raspberry Pi OS / Mint etc**

    sudo systemctl restart php7.1-fpm
    sudo systemctl restart nginx

**Arch / Manjaro etc**

    sudo systemctl enable php-fpm
    sudo systemctl enable nginx
    sudo systemctl start php-fpm
    sudo systemctl start nginx

That should be it. Direct your browser to http://hostname.of.your.computer/rompr (or http://ip.address.of.your.computer/rompr) and all should be well.
