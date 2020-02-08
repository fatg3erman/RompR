# Installing on macOS with nginx webserver

This is an alternative method to install RompЯ on macOS which uses the nginx webserver instead of the built in Apache server. This method installs everything from Homebrew, which should future-proof it better against Apple removing stuff from macOS in the future.

## Install Homebrew

[Homebrew](https://brew.sh/)

## Installing a player

You'll need either MPD or Mopidy. However as at time of writing Mopidy installation is not working on macOS at all.

### Install Mopidy...

The instructions on Mopidy's website [Mopidy](https://docs.mopidy.com/en/latest/installation/osx/) don't currently work.

Then go [here](https://discourse.mopidy.com/t/cant-run-mopidy-on-fresh-brew-install-getting-python-framework-error/2343/2)

### ... or install MPD

    brew install mpd

## Install Rompr

First open Terminal. If you haven't used Terminal before, don't be scared. It's under 'Other' or 'Utilities'. Type commands exactly as they appear here, and enter your Mac password whenever you are asked.

    cd ~
    mkdir Sites

Now if you've downloaded the ZIP file from here, you can copy it into the Sites folder you just created above and unzip it. Probaby you just need to double-click it to do that.
Now go back to that terminal window and we'll set some permissions.

    cd Sites/rompr
    mkdir prefs
    mkdir albumart

## Install nginx webserver

### nginx installation

    brew install nginx imagemagick

### nginx configuration

Now you need to edit a configuration file to tell nginx which user it should run as. It needs to be the same as the user you log in as. if you're not sure what that is, type

    whoami

and make a note of the response.

    nano /usr/local/etc/nginx/nginx.conf

At the top of that file, you need to change the setting for user. This gives nginx permission to read and write to your rompr installation. So here, where I've put "username" you should put your user login name, as told to you by 'whoami' above.

    user username staff;

Now hit ctrl-X to exit. Answer 'Y' and hit enter when it asks you if you want to save the file.

### RompЯ configuration

We're going to create a configuration for rompr

    nano /usr/local/etc/nginx/servers/rompr

This will create an empty file, into which you should paste the following (cmd-V will paste):

    server {

        listen 80 default_server;
        listen [::]:80 default_server;

        root /PATH/TO/ROMPR;
        index index.php index.html index.htm;

        server_name www.myrompr.net;

        client_max_body_size 256M;

        # This section can be copied into an existing default setup
        location / {
                allow all;
                index index.php;
                location ~ \.php {
                        try_files $uri index.php =404;
                        fastcgi_pass unix:/usr/local/var/run/php.sock;
                        fastcgi_index index.php;
                        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
                        fastcgi_split_path_info ^(.+\.php)(/.+)$;
                        include fastcgi_params;
                        fastcgi_read_timeout 1800;
                        fastcgi_buffers 16 16k;
                        fastcgi_buffer_size 32k;
                }
                error_page 404 = /404.php;
                try_files $uri $uri/ =404;
                location ~ /albumart/* {
                        expires -1s;
                }
        }
    }

You must edit where I've put /PATH/TO/ROMPR to be the directory where you rompr files are. If you've followed these instructions exactly, this will be (again, replace"username" with the username that was the response to the 'whoami' command)

    /Users/username/Sites/rompr

Again hit ctrl-X and the answer Y to save that file.

## Install PHP

### PHP installation

    brew install php@

### PHP configuration

The exact location of the config files will depend on the version of PHP that Homebrew has decided to install. At the time of writing, it was 7.4 and it should be obvious where this might need to be changed in the commands that follow

    nano /usr/local/etc/php/7.4/php-fpm.d/www.conf

Ctrl-W is 'find' in nano.

First find and edit the user and group entries - as before "username" should be your username

    user = username
    group = staff

Now find and modify the following:

    listen = /usr/local/var/run/php.sock

For performance reasons I like to also change

    pm.max_children = 10

or an even higher number but this uses up more memory and is not essential. I'll leave it up to you.

As usual, ctrl-X and then answer 'Y'.

Now edit the php ini file:

    nano /usr/local/etc/php/7.4/php.ini

and edit the following parameters:

    allow_url_fopen = On
    memory_limit = 128M
    max_execution_time = 1800
    upload_max_filesize = 10M
    max_file_uploads = 200
    post_max_size = 256M

(The last 3 entries are really only used when uploading [Custom Background Images](/RompR/Theming). They set, respectively, the maximum size of an individual file (in megabytes), the maximum number of files you can upload in one go, and the maximum total size (megabytes) you can upload in one go. The values above are just examples - but note that post_max_size has an equivalent called 'client_max_body_size' in the nginx config file and it's sensible to keep them the same).

As usual, ctrl-X and then answer 'Y'.

## Install Some Additional Bits

If you're using Mopidy

    brew install gst-libav

## Edit Hosts Definition

You may have noticed we used www.myrompr.net above. We need the OS to know where that is

    sudo nano /etc/hosts

and add a line

    127.0.0.1   www.myrompr.net

On other devices you can either add an entry to their hosts file for the IP address of your mac (eg 192.168.1.5 www.myrompr.net) or you just enter that IP address directly into the browser.

## Start Everything

    sudo brew services start php
    sudo brew services start nginx

## And We're Done

Your browser can now be pointed at www.myrompr.net.

To access rompr from another device you need to edit the hosts file there too. If you can't edit the hosts file, just use the computer's IP address.
