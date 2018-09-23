# How To Install RompЯ on Windows

The installation on Windows is slightly more complex than on Linux or macOS, you can only use MPD, and it isn't as fast or as responsive, but it does work. Rompr doesn't work in IE but it might work in Edge.

There are many possible ways to do this. This page describes the one I've tried on Windows 10.

## Install MPD

Download the most recent version of MPD for Windows from https://www.musicpd.org/download.html

Make a new folder C:\mpd and copy mpd.exe into it.

Now you need to create an mpd.conf file. An example one is below. This example only contains the parts I had to modify from the standard example which can be found at https://github.com/tx4x/mpd-windows/blob/master/mpd.conf

    # Files and directories #######################################################
    #
    # This setting controls the top directory which MPD will search to discover the
    # available audio files and add them to the daemon's online database. This
    # setting defaults to the XDG directory, otherwise the music directory will be
    # be disabled and audio files will only be accepted over ipc socket (using
    # file:// protocol) or streaming files over an accepted protocol.
    #
    music_directory                       "C:\\Users\\bob\\Music"
    #
    # This setting sets the MPD internal playlist directory. The purpose of this
    # directory is storage for playlists created by MPD. The server will use
    # playlist files not created by the server but only if they are in the MPD
    # format. This setting defaults to playlist saving being disabled.
    #
    playlist_directory                    "C:\\mpd\\data\\playlists"
    #
    # This setting sets the location of the MPD database. This file is used to
    # load the database at server start up and store the database while the
    # server is not up. This setting defaults to disabled which will allow
    # MPD to accept files over ipc socket (using file:// protocol) or streaming
    # files over an accepted protocol.
    #
    db_file                               "c:\\mpd\\data\\database"
    #
    # These settings are the locations for the daemon log files for the daemon.
    # These logs are great for troubleshooting, depending on your log_level
    # settings.
    #
    # The special value "syslog" makes MPD use the local syslog daemon. This
    # setting defaults to logging to syslog, otherwise logging is disabled.
    #
    log_file                              "c:\\mpd\\data\\log"
    #
    # This setting sets the location of the file which stores the process ID
    # for use of mpd --kill and some init scripts. This setting is disabled by
    # default and the pid file will not be stored.
    #
    pid_file                              "c:\\mpd\\data\\pid"
    #
    # This setting sets the location of the file which contains information about
    # most variables to get MPD back into the same general shape it was in before
    # it was brought down. This setting is disabled by default and the server
    # state will be reset on server start up.
    #
    state_file                            "c:\\mpd\\data\\state"
    #
    # The location of the sticker database.  This is a database which
    # manages dynamic information attached to songs.
    #
    sticker_file                          "c:\\mpd\\data\\sticker.sql"
    #

    audio_output {
                    type                  "winmm"
                    name                  "Focusrite Solo"
                    device                "Focusrite USB (Focusrite USB Audio)"
    }

You should configure the music_directory to be the path where the music is on your computer. The others I suggest to leave as they are here. Note also the use of double-backslashes in the paths, this is essential.

The audio_output section contains the sound card you want to use. If you only have one you can omit the 'name' and 'device' lines. If you want to specify a device like I have here, the device section should be the name of the device as it appears under Windows Settings.

Save your mpd.conf to

    c:\mpd\mpd.conf

Best thing to do now is open a Command Prompt and type the following:

    C:
    cd C:\mpd
    mkdir data
    mkdir data\playlists

    mpd c:\mpd\mpd.conf

This will create the data directories mpd needs, and then start it. MPD is now running. Provided you don't see any errors you're fine. You will see something about it not being able to open the database file, but that's normal and can be ignored.

## Install Bitnami WAMP

We need to have MySQL, PHP, and Apache running on your Windows PC. The easiest way to do this is with what's known as a WAMP stack. There are many of these. Here I describe how to use Bitnami, which is one such offering and is free.

Download the installer from https://bitnami.com/stack/wamp/installer

Install the software by running the installer. Early on it will ask if you want to install several extras, such as Drupal, Wordpress, etc. You can answer No to all of these. It will also ask you to set a root password for your MySQL installation. You should do this and remember it as you'll need it later.

Once WAMP is installed it should present you with a control panel which allows you to restart MySQL and Apache. Keep this open as you'll need it in a minute.

### Add a rompr 'app' to Bitnami

Now we're going to install RompЯ as an 'app' within the Bitnami environment. You can read up on this if you like, or you can simply follow these steps.

The Bitnami installer will have installed the software to somewhere like

    C:/Bitnami/wampstack-7.1.21-0

 the exact path will vary depending on which version you installed. The Bitnami documentation refers to that as 'installdir'. I've not used that below, so if your version is different you'll have to edit the following commands as appropriate.

 Open another command prompt window and do the following:

    mkdir C:/Bitnami/wampstack-7.1.21-0/apps/rompr
    mkdir C:/Bitnami/wampstack-7.1.21-0/apps/rompr/htdocs/
    mkdir C:/Bitnami/wampstack-7.1.21-0/apps/rompr/conf

Now you need to save the following lines as the file C:/Bitnami/wampstack-7.1.21-0/apps/rompr/conf/httpd-prefix.conf

    Alias /rompr/ "C:/Bitnami/wampstack-7.1.21-0/apps/rompr/htdocs/"
    Alias /rompr "C:/Bitnami/wampstack-7.1.21-0/apps/rompr/htdocs/"
    Include "C:/Bitnami/wampstack-7.1.21-0/apps/rompr/conf/httpd-app.conf"

And the following as C:/Bitnami/wampstack-7.1.21-0/apps/rompr/conf/httpd-app.conf

    <Directory C:/Bitnami/wampstack-7.1.21-0/apps/rompr/htdocs/>
        Options Indexes FollowSymLinks Includes ExecCGI
        DirectoryIndex index.php
        AllowOverride All
        <IfVersion < 2.3 >
          Order allow,deny
          Allow from all
        </IfVersion>
        <IfVersion >= 2.3>
          Require all granted
        </IfVersion>
        AddType image/x-icon .ico

        <IfModule mod_php7.c>
          AddType application/x-httpd-php .php
          php_flag magic_quotes_gpc Off
          php_flag track_vars On
          php_admin_flag allow_url_fopen On
          php_value include_path .
          php_admin_value upload_tmp_dir  C:/Bitnami/wampstack-7.1.21-0/apps/rompr/htdocs/prefs/temp
          php_admin_value open_basedir none
          php_admin_value memory_limit 128M
          php_admin_value post_max_size 32M
          php_admin_value upload_max_filesize 32M
          php_admin_value max_execution_time 1800
        </IfModule>

    </Directory>

    <Directory C:/Bitnami/wampstack-7.1.21-0/apps/rompr/htdocs/albumart/small>
      Header Set Cache-Control "max-age=0, no-store"
      Header Set Cache-Control "no-cache, must-revalidate"
    </Directory>

    <Directory C:/Bitnami/wampstack-7.1.21-0/apps/rompr/htdocs/albumart/medium>
      Header Set Cache-Control "max-age=0, no-store"
      Header Set Cache-Control "no-cache, must-revalidate"
    </Directory>

    <Directory C:/Bitnami/wampstack-7.1.21-0/apps/rompr/htdocs/albumart/asdownloaded>
      Header Set Cache-Control "max-age=0, no-store"
      Header Set Cache-Control "no-cache, must-revalidate"
    </Directory>

Finally edit the file C:/Bitnami/wampstack-7.1.21-0/apache2/conf/bitnami/bitnami-apps-prefix.conf, and add the following line to the end of it:

    Include "C:/Bitnami/wampstack-7.1.21-0/apps/rompr/conf/httpd-prefix.conf"

Now you can return to the control panel that opened earlier and restart the Apache Web Server

## Create the MySQL database

We need to use MySQL, since the WAMP stack doesn't include support for SQLite. Open your browser and go to

    http://localhost/

You should see the Bitnami welcome page. This changes so it's hard to provide accurate instructions, but try to find 'Apps' and then 'PHPMyAdmin'. Once you've got PHPMyAdmin open, log in to MySQL as root using the password you created earlier. Under Databases, create a new database called romprdb with the collation utf8_unicode_ci.

Now add a new user called rompr, with a password romprdbpass and give that user full permissions on the database romprdb.

If you're struggling with this, the documentation for PHPMyAdmin online is quite good.

## Install RompR

Download the latest release of RompR and unzip it. This will give you a directory called rompr-x.yz. In that directory will be a directory called rompr. Copy the *contents* of the rompr directory to

    C:/Bitnami/wampstack-7.1.21-0/apps/rompr/htdocs/

and then do the following

    mkdir C:/Bitnami/wampstack-7.1.21-0/apps/rompr/htdocs/albumart
    mkdir C:/Bitnami/wampstack-7.1.21-0/apps/rompr/htdocs/prefs

One further thing to do is that, because we're on Windows, the Path To Local Music option won't work and so you won't be able to get album art or lyrics from your music files. To fix this:

    C:
    cd C:/Bitnami/wampstack-7.1.21-0/apps/rompr/htdocs/prefs
    mklink /D MusicFolders c:\path\to\your\music

where c:\path\to\your\music must be the same as music_directory in your mpd.conf.

Note that you must *never* set the Path To Local Music option in Rompr's configuration, as that will remove the link you just created.

## Configure RompR

Now go to

    http://localhost/rompr?setup

and choose to use the 'Full Database Collection'. Leave all the other options as defaults and click OK. Rompr should now open and you can create your Music Collection.

## Running MPD as a service

It'll be annoying to have to start MPD in a command prompt every time you want to run it, so you can create a service to do it.

First, go back to the command prompt you opened earlier with MPD running in it and hit Ctrl-C (make sure you're not currently Updating your Music Collection).

Now you need an *Administrator* command prompt, which you get by right-clicking on Command Prompt in the start menu and choosing 'Run As Administrator'.

In that window, do (copy-and-paste this exactly as shown):

    sc create mpd binPath= "c:\mpd\mpd.exe c:\mpd\mpd.conf"

Start the Services console. You can get to it by typing services.msc into the Start Menu/Screen search (Run for XP or older).

In the tool, find the mpd service. Go to the Log On tab, choose This account: and enter your credentials there. Hit Apply and go to the General tab, on which you should choose the Startup type to be Automatic (Delayed Start). Finish by pressing Start. MPD should be running and configured properly. You can now hit OK and close the Services window, along with the command prompts you have open.

Warning: if you change your Windows password, you need to change your password in the Services console as well!

