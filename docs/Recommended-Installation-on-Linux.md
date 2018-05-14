# Installation On Linux

RompЯ is a client for mpd or mopidy - you use RompЯ in a web browser to make mpd or mopidy play music
These are basic installation instructions for RompЯ on Linux, using the code you can download from here on github.

**The old project homepage is at [SourceForge](https://sourceforge.net/projects/rompr/). The old discussion forum is still there and you may find answers to some questions is you have them.**

## Assumptions

I'm going to assume you already have mpd or mopidy installed and working. This is not the place to discuss the arcane art of configuring mpd. For that you'll have to read the mpd community wiki. Sorry about that. The mopidy instructions are quite good.

## Recommended Setup for Linux

This is the way I now recommend you do it. Thanks to the anonymous forum user who put up the initial instructions for getting it to work with PHP7 when I was in the wilderness.

_The following is a guide. It has been tested on Kubuntu 17.10 so Ubuntu and Debian flavours should follow this. Other distributions will be similar but package names may be different and the location of files may be different. Sorry, I can't try them all. If only they'd agree._

This guide sets up RompЯ to work with the nginx web server, an sqlite database and allows you to access it using a nice url - www.myrompr.net

### Install RompЯ

Download the latest release from [The Github Releases Page](https://github.com/fatg3erman/RompR/releases)

Let's assume you extracted the zip file into a folder called 'web' in your home directory. So now you have /home/YOU/web/rompr. From now on we're going to refer to that as /PATH/TO/ROMPR, because that's what programmers do and it makes the guide more general. You can put the code anywhere you like, although it won't work very well if you put it in the oven. So you'll need to look out for /PATH/TO/ROMPR in everything below and make sure you substitute the correct path.

### Set directory permissions

We need to create directories to store data in.

    cd /PATH/TO/ROMPR
    mkdir prefs
    mkdir albumart
    mkdir albumart/small
    mkdir albumart/asdownloaded


And then we need to give nginx permission to write to them. We can do this by changing the ownership of those directories to be the user that nginx runs as. This may differ depending on which distro you're running, but this is good for all Ubuntus, where nginx runs as the user www-data.

    sudo chown -R www-data /PATH/TO/ROMPR/albumart
    sudo chown -R www-data /PATH/TO/ROMPR/prefs


### Install some packages

    sudo apt-get install php7.1-sqlite3 nginx php7.1-curl imagemagick php7.1-json php7.1-fpm php7.1-xml php7.1-mbstring

_Note the version numbers - 7.1 is current at the time of  writing but as times change it may become 7.2,etc. On Ubuntu 16.04 I think it is 7.0. Amend the command as applicable_

### Create nginx configuration

We're going to create RompЯ as a standalone website which will be accessible through the address www.myrompr.net

_Note. This sets RompЯ as the default site on your machine. For most people this will be the best configuration. If you are someone who cares about what that means and understands what that means, then you already know how to add RompЯ as the non-default site. What is described here is the easiest setup, which will work for most people_

First we will remove the existing default config, since we don't want it.

    sudo unlink /etc/nginx/sites-enabled/default

Then we will create the rompr config and set that to be the default

    sudo nano /etc/nginx/sites-available/rompr

Paste in the following lines, remembering to change /PATH/TO/ROMPR as above, and edit the 7.1 if appropriate.

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
                    fastcgi_pass unix:/var/run/php/php7.1-fpm.sock;
                    fastcgi_index index.php;
                    fastcgi_param SCRIPT_FILENAME $request_filename;
                    include /etc/nginx/fastcgi_params;
                    fastcgi_read_timeout 600;
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

### Edit the hosts file

To make your browser capable of accessing www.myrompr.net we need to edit your hosts file so the computer knows where www.myrompr.net actually is.

    sudo nano /etc/hosts

and just add the line

    127.0.0.1        www.myrompr.net

You will need to make this change on every device you want to access rompr from - with an appropriate IP address. On devices where this is not possible - eg a mobile device - you can just enter the IP address of your web server into your browser to access RompЯ (because we have set RompЯ as the default site).

_Those of you who want to be clever and know how to edit hostname and DNS mapping on your router can do that, you will then not need RompЯ to be default site and you will not need to remove the existing default config. Just remove default_server where it appears above and set server_name appopriately. If you didn't understand that, then ignore this paragraph._

### Edit PHP configuration

We need to edit the PHP configuration file.

    sudo nano /etc/php/7.1/fpm/php.ini

Now find and modify (or add in if they're not there) the following parameters. Ctrl-W is 'find' in nano.

    allow_url_fopen = On
    memory_limit = 128M
    max_execution_time = 300

### That's all the configuring. Let's get everything running

    sudo systemctl restart php7.1-fpm
    sudo systemctl restart nginx

That should be it. Direct your browser to www.myrompr.net and all should be well.
