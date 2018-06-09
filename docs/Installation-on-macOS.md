# macOS (with Apache webserver)

Getting this to work on macOS gets harder by the release, but it's not actually that much of a problem. This guide should work on macOS High Sierra.

First open Terminal. If you haven't used Terminal before, don't be scared. It's under 'Other' or 'Utilities'. Type commands exactly as they appear here, and enter your Mac password whenever you are asked.

## 1. Installing a player

You'll need either MPD or Mopidy

### Install Mopidy...

The instructions on mopidy's website no longer work, so

First install [Homebrew](https://brew.sh/)

Then go [here](https://discourse.mopidy.com/t/cant-run-mopidy-on-fresh-brew-install-getting-python-framework-error/2343/2)

### ... or install MPD

First install [Homebrew](https://brew.sh/)

Then

    brew install mpd --with-opus --with-libmss

## 2. Installing Rompr

    cd ~
    mkdir Sites
    
Now if you've downloaded the ZIP file from here, you can copy it into the Sites folder you just created above and unzip it. Probaby you just need to double-click it to do that.
Now go back to that terminal window and we'll set some permissions.

    cd Sites/rompr
    mkdir prefs
    mkdir albumart
    sudo chown _www prefs
    sudo chown _www albumart

## 3. Configure Apache Web Server

This can get a little arcane but it's not all that complicated. There are, of course, a thousand ways to acheive the same thing, and googling will inevitably find differences.

### 3a. httpd.conf

    sudo nano /private/etc/apache2/httpd.conf

This opens a configuration file in a small text editor called nano. You need to use cursor keys to move around. ctrl-W is 'Search', and you'll find that useful.

What you need to do is to search for the lines mentioned below - search for a major part of the line and make sure it looks as written here. The most important part is the presence or absence of a # at the start.

    LoadModule headers_module libexec/apache2/mod_headers.so
    LoadModule php7_module libexec/apache2/libphp7.so
    Include /private/etc/apache2/other/\*.conf

When you've done that, hit Ctrl-X and then answer 'Y' (and hit Enter) to save the file and exit nano.

That's the hardest bit, but we're not done yet.
There's another file we need to edit with nano

### 3b. httpd_dirs.conf

    sudo nano /private/etc/apache2/other/httpd_dirs.conf

This will open nano again. It may bring up an empty file, or it may bring up a file with stuf in it. Just paste the following on the end (cmd-V to paste). Edit YOURNAME so it matches your home directory

    <VirtualHost \*:80>
	    DocumentRoot /Users/YOURNAME/Sites/rompr
	    ServerName www.myrompr.net
        ErrorDocument 404 /404.php

	    <Directory /Users/YOURNAME/Sites/rompr>
            Options Indexes FollowSymLinks Includes ExecCGI
           DirectoryIndex index.php
           AllowOverride All
           Require all granted
           AddType image/x-icon .ico


		    <IfModule mod_php7.c>
			    AddType application/x-httpd-php .php
			    php_flag magic_quotes_gpc Off
			    php_flag track_vars On
			    php_admin_flag allow_url_fopen On
			    php_value include_path .
			    php_admin_value upload_tmp_dir /Users/YOURNAME/Sites/rompr/prefs/temp
			    php_admin_value open_basedir none
    		    php_admin_value memory_limit 128M
                php_admin_value post_max_size 32M
                php_admin_value upload_max_filesize 32M                
		    </IfModule>

	    </Directory>

	    <Directory /Users/YOURNAME/Sites/rompr/albumart/small>
	        Header Set Cache-Control "max-age=0, no-store"
	        Header Set Cache-Control "no-cache, must-revalidate"
	    </Directory>

	    <Directory /Users/YOURNAME/Sites/rompr/albumart/asdownloaded>
	        Header Set Cache-Control "max-age=0, no-store"
	        Header Set Cache-Control "no-cache, must-revalidate"
	    </Directory>

    </VirtualHost>

Once again, Ctrl-X and answer Y to save the file.

### 3c. php7.conf

There's one more

    sudo nano /private/etc/apache2/other/php7.conf

and this is what you need in that file

    <IfModule php7_module>
        AddType application/x-httpd-php .php
        AddType application/x-httpd-php-source .phps

        <IfModule dir_module>
                DirectoryIndex index.html index.php
        </IfModule>
    </IfModule>

### 3d. Testing The Configuration

    sudo apachectl configtest

This will report any errors with your config files. Hopefully there won't be any but if there are hopefully they make sense and you can fix them.

Ignore anything to do with 'Could not reliably determine the server's fully qualified domain name', that's entirely normal and nothing to worry about.

Assuming all is OK

    sudo apachectl restart

## 4. Install Some Additional Bits
We're nearly there. So nearly.

    brew install imagemagick


## 5. Edit Hosts Definition

You may have noticed we used www.myrompr.net above. We need the OS to know where that is

    sudo nano /etc/hosts

and add a line

    127.0.0.1	www.myrompr.net

## 6. And We're Done##

Your browser can now be pointed at www.myrompr.net.
To access rompr from another device you need to edit the hosts file there too. If you can't edit the hosts file, just use the computer's IP address.
