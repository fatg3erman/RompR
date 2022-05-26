# How To Install RompЯ

To run RompЯ you will need to install a few things on your system, and there are many many ways to do it. Here I'm going to explain a few of the options and choices you have,
along with some reccomendations.

## First, You Need A Player

RompЯ doesn't play any music itself. You need a player (or multiple players) to do that. You have a choice of player:

### MPD - The Music Player Daemon

The original [Music Player Daemon](https://www.musicpd.org/) has been around for ages, is rock-solid and fast. It plays local music, internet radio, and works great with RompЯ's podcast engine too.
It can also be made to play from some online sources, but Mopidy usually does a better job of that.
On Linux there is always a package available to install it. On macOS it is available through Homebrew.

You need to change one thing in your mpd configuration for it to work well with RompЯ

Somewhere in mpd.conf

    connection_timeout     "120"

### Mopidy - The All-Rounder

[Mopidy](http://www.mopidy.com) is similar to MPD. It does everything MPD does, but it also plays music from many online sources including Spotify and SoundCloud. I like Mopidy and I use it a lot,
and a lot of RompЯ's Music Discovery features rely on Spotify support. But Mopidy isn't very well maintained any more and it has more bugs than MPD.

RompЯ requires Mopidy's MPD interface, and can also use its HTTP interface if present. As a minimum you will need to install mopidy and mopidy-mpd. To get local files playback support install mopidy-local, and for Spotify install mopidy-spotity.

In your mopidy.conf you should ensure the following are set:

    [mpd]
    enabled = true
    max_connections = 30
    connection_timeout = 120

    [http]
    enabled=true
    hostname = 0.0.0.0
    port=6680
    csrf_protection=false

Also you probably should read [RompЯ And Mopidy](/RompR/Rompr-And-Mopidy)

## Next, You Need To Install RompЯ Somewhere

**Note**

RompЯ uses Cookies and will store preferences in the local settings of your browser. It will not function without the cookies and if you use a private or incognito session you will
lose most of your preferences when you close it. If you're not happy with RompЯ using cookies or you do not want it to save data on your device, do not install it.

All of the settings you save in RompЯ are stored in plain text. If you log in to Last.FM RompЯ does not store your Last.FM password
but it does store a login key that could be used to gain access to your account. Similarly if you give RompЯ a Bing API key for image search that too will be stored in plain text.
No sensitive login information is stored in your browser, all of that is saved to the web server backend in the file prefs/prefs.var.

Download the latest release from [The Github Releases Page](https://github.com/fatg3erman/RompR/releases)

Let's assume you extracted the zip file into a folder called 'web' in your home directory. So now you have /home/YOU/web/rompr, or on macOS /Users/YOU/web/rompr - where 'YOU' is your login username.
You could put it anywhere but your home directory is easiest.

**Note - on Arch Linux, and Manjaro, and other related distributions, unzipping it into your home directory will not work due to some permissions issues**
On these distributions you will need to extract it to /var/www, meaning you have /var/www/rompr.
Or you can change the permissions on your home directory by doing the following (note that this gives all users on the system read access to your home directory)

    sudo chmod go+rw /home/YOU

I'm going to assume in all the following instructions that you have installed to /home/YOU/web/rompr, so adjust as necessary if you put it somewhere else.

### Set directory permissions

We need to create directories to store data in.

    cd /home/YOU/web/rompr
    mkdir prefs
    mkdir albumart

And then we need to give the web server permission to write to them.

**Debian / Ubuntu / Raspberry Pi OS / Mint etc**

    sudo chown www-data:www-data albumart
    sudo chown www-data:www-data prefs

**Arch / Manjaro etc**

    sudo chown http:http albumart
    sudo chown http:http prefs

The above commands work by changing the owner of the directories to the user the webserver runs as. If you don't know what that is, then you can do the
following instead, but note that this gives everybody on the system write access.

    chmod ugo+rw albumart
    chmod ugo+rw prefs

## Next, You Need A Web Server

This is where you have another choice to make. There are a lot of web servers out there and configuring them is never a simple task.
What I've tried to do here is to create a few guides to how to get it working with two of the most popular ones (Apache and nginx).
Which one you choose will depend on you. I like nginx, it works well on smaller system like Raspberry Pi.
But you may already be running Apache on your system if you have some other web service already installed, like MythWeb for example.

[RompЯ With Nginx on Linux](/RompR/Recommended-Installation-on-Linux)

[RompЯ With Apache on Linux](/RompR/Installation-on-Linux-Alternative-Method)

[RompЯ With Nginx on macOS](/RompR/macOS-With-Nginx)

[RompЯ With Apache on macOS](/RompR/Installation-on-macOS)

[RompЯ On Android](/RompR/Installation-on-Android)

There is also the option to install everything you need using Docker:

[Docker Container](/RompR/Installation-with-Docker)

The default installation uses an SQLite database, which is fine for nearly everybody. If you'd like to use a MySQL database instead there is [A Guide](/RompR/Using-a-MySQL-server)