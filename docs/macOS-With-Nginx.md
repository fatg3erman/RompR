# Installing on macOS with nginx webserver

This is an alternative method to install RompЯ on macOS which uses the nginx webserver instead of the built in Apache server.

## Installing a player

You'll need either MPD or Mopidy

### Install Mopidy...

The instructions on mopidy's website no longer work, so first install [Homebrew](https://brew.sh/)

Then go [here](https://discourse.mopidy.com/t/cant-run-mopidy-on-fresh-brew-install-getting-python-framework-error/2343/2)

### ... or install MPD

First install [Homebrew](https://brew.sh/)

Then

    brew install mpd --with-opus --with-libmss

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

    brew tap homebrew/homebrew-php
    brew install php72

Note: the '72' refers to the version number of PHP - in this case version 7.2. This was current at the time of writing but it may change in the future. 'brew search php' will give you a long list of stuff with various version numbers, probably 54, 55, 70, 72, and upwards. I'd suggest using 72 as I know it works, but if there's a newer version that should be OK too.

### PHP configuration

    nano /usr/local/etc/php/7.2/php-fpm.d/www.conf

First find and edit the user and group entries - as before "username" should be your username

    user = username
    group = staff

Now change the entry for 'listen'.

    listen = /usr/local/var/run/php.sock

For performance reasons I like to also change the entry for pm.max_children to at least 10, but this uses up more memory and is not essential. I'll leave it up to you.
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

    sudo brew services start homebrew/php/php72
    sudo brew services start nginx

## And We're Done

Your browser can now be pointed at www.myrompr.net.

To access rompr from another device you need to edit the hosts file there too. If you can't edit the hosts file, just use the computer's IP address.
