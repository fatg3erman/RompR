# Using RompЯ with Mopidy

[Mopidy](http://www.mopidy.com/) is an mpd-like player that does everything mpd does and more. It plays Spotify, for one thing :)

If you use Mopidy, please make sure you read the following to ensure you get the best out of it.

## Communication with Mopidy

RompЯ communicates with mopidy using its MPD frontend.
Mopidy version 1.1 or later is required.

## Building Your Music Collection

The configuration panel will allow you to choose various sources from which to build your [Music Collection](/RompR/Music-Collection).

![](images/buildcollectionfrom.png)

You will only see options for backends that are enabled in Mopidy. The complete list of supported backends is:

* **Local Music** [('local' backend must be enabled)](/RompR/Rompr-And-Mopidy)
* **Beets** [('beets' backend must be enabled)](/RompR/Rompr-And-Mopidy)
* **Beets Local** ('beetslocal' backend must be enabled)
* **Spotify Playlists** ('spotify' backend must be enabled) *This will add all tracks from your Spotify Playlists into your collection. Your playlists will still be available as Playlists even if you don't select this option*
* **Spotify 'Your Music'** ('spotify-web' backend must be enabled)
* **Spotify 'Your Artists'** ('spotify-web' backend must be enabled)
* **Google Music** ('gmusic' backend must be enabled)
* **SoundCloud 'Liked'** ('soundcloud' backend must be enabled)
* **SoundCloud 'Sets'** ('soundcloud' backend must be enabled)
* **VKontakte** ('vkontakte' backend must be enabled)


If you don't want to build a collection this way, tracks from anywhere can be added to the collection by tagging or rating them at any time.


Tagging or rating a track that is playing on a radio station will make RompЯ search for it on Spotify (if you have Spotify) and add it to your collection if it can find it, or to your [wishlist](/RompR/The-Wishlist) if it can't.

## If you use Mopidy-Beets

You can create your Music Collection from your Beets Library by selecting the option in the Configuration Panel. There is also a box to enter the address of your Beets server. This is not required for building the Music Collection, but if you set this value then you will be able to retrieve additional file information and lyrics from your Beets server.

![](images/mopcolbeets.png)

You need to make sure that your browser can access your Beets server for this to work. If your browser runs on a different computer than your beets server, then your beets config.yaml needs to contain

    web:
      host: IP.address.of.beets.server

Otherwise beets will not allow RompЯ to talk to it. Your configuration for beets in mopidy must also contain this IP address as Beets will only communicate via the supplied IP address.

## If you use Mopidy-Local-Sqlite

There seems to be a bug in the scanner engine in mopidy's sqlite backend where sometimes it puts tracks on the wrong albums. Putting the following in your mopidy configuration seems to work around this.

    [local-sqlite]
    enabled = true
    use_album_mbid_uri = false
    use_artist_sortname = false
    
## Scanning Local Files

Where MPD provides an 'update' command that RompЯ can use to update MPD's music database, Mopidy does not and so RompЯ can not easily make Mopidy scan local files - this has to be done with the 'mopidy local scan' command, which cannot be run directly by RompЯ . However it is reasonably straightforward to implement a solution to this problem - if (and only if) Mopidy is running on the same computer as your web server.

**NOTE** This is something of a hack, and using setuid is a potential security risk. If your web server is accessible to the internet you should make sure you understand the implications of using setuid before you do this.

**ALSO NOTE** This will only work properly if you're using Mopidy-Local-Sqlite for your local files, as the default json backend requires mopidy to be restarted after the scan. If you know what you're doing you can make the shell script do that but using local-sqlite is just easier.

If you don't want to do this, then you can just run mopidy local scan yourself before updating RompЯ's collection.

### 1. Finding the correct user

The first part of the problem is that 'mopidy local scan' must be run as the same user that Mopidy runs as. Any process started by RompЯ will run as the user that Apache (or nginx) runs as, and they are not the same. So the first thing we need to know is which user we need to use. Assuming mopidy is running, type

    ps aux | grep mopidy
    
and you will get back something like

    bob       1179  0.9  2.6 182456 47404 ?        Sl   18:20   2:45 /usr/bin/python /usr/bin/mopidy
    bob       7936  0.0  0.0   4696   840 pts/2    S+   23:24   0:00 grep --color=auto mopidy
    
The first line shows that, on my system, mopidy is running as the user 'bob' - which is the user I log in as. This is almost always the case but it's best to check. (The second line is simply the 'grep' process we started in order to look for mopidy in the first place).

### 2. Creating the Mopidy Scan Command

We need to create a simple shell script that will run mopidy local scan as the correct user.

So create a file called **mopidy_scan.sh** (the filename MUST be mopidy_scan.sh) with the following contents

**EITHER** If you're [running mopidy as a service](https://docs.mopidy.com/en/latest/service/#service) then the file needs to contain

    #!/bin/bash

    mopidyctl local scan

**OR** If you're just running mopidy as a login program

    #!/bin/bash

    mopidy local scan

and save it somewhere. I put mine in /home/bob/bin/mopidy_scan.sh. Where you save it doesn't matter, but the filename does.

Now we need to make the script executable and use setuid to make it run as the correct user

    chmod +x /home/bob/bin/mopidy_scan.sh
    chmod u+s /home/bob/bin/mopidy_scan.sh

(Change the filename and path according to wherever you saved it)
    
**IF** the user you found in step 1 is not the same as your login user

    sudo chown USER /home/bob/bin/mopidy_scan.sh

where USER is the username you found in step 1.    
(Change the filename and path according to wherever you saved it)

**OR IF** you're [running mopidy as a service](https://docs.mopidy.com/en/latest/service/#service)

    sudo chown root /home/bob/bin/mopidy_scan.sh
    
(In this case, as setuid root is a very big security risk, I suggest putting mopidy_scan.sh somewhere safe and not on the system PATH)

### 3. Configure RompЯ

In your browser, go to

    http://your.rompr.instalation?setup
    
and enter the FULL PATH to your shell script in the 'Mopidy Local Scan Command' box

![](images/mopidyscan.png)

Click 'OK' at the bottom of the window, and you're ready to go. RompЯ can now make Mopidy scan your local files.

### 4. Checking

If you want to check that it's working, enable [debug logging](/RompR/Troubleshooting) from the setup screen and check your web server's error log (set debug logging to Level 4 or higher).

You should see a line that says

    COLLECTION  Starting Mopidy Scan Command
    
and

    COLLECTION  Mopidy Scan Process has completed
    
If you set debug logging to Level 8 you will also see

    COLLECTION  Checking For Mopidy Scan Process
    
which will be repeated multiple times if the update takes a long time.
