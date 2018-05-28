# Using RompЯ with mobile devices

## Updating RompЯ

Unfortunately it seems that many mobile devices use very strong cache settings and when you update RompЯ it may not run properly on your device. The only cure for this seems to be to completely delete the cache on your device then load RompЯ. If you have added RompЯ as an icon on your home scree, you will have to remove and recreate the icon.

## Playcounts and Scrobbling

RompЯ works fine on almost all mobile devices but, because it runs in a web browser, if the device goes to sleep (the screen switches off) then RompЯ will not be able to update Playcounts or scrobble tracks to Last.FM. This page explains how to set up some extras so that this still works.

One option is to always leave a desktop browser open on RompЯ and that will take care of everything, but that won't be an option for most people, and the following is neater anyway.

## Scrobbling to Last.FM

To make sure your tracks get scrobbled you should look at using [mopidy-scrobbler](https://github.com/mopidy/mopidy-scrobbler) for Mopidy or [mpdscribble](https://www.musicpd.org/clients/mpdscribble/) for mpd.

## Updating RompЯ's Playcounts

Making RompЯ update Playcounts is only slightly harder. Even if you don't care about Playcounts, they are used by many of the [Personalised Radio Stations](/RompR/Personalised-Radio), so it's still useful.

RompЯ is provided with a small program called romonitor that takes care of updating playcounts (and also marks podcasts as listened). It just needs a little setting up.

### Create a Shell Script to run romonitor

Create a file somewhere (anywhere) called romonitor.sh, based on the following template:

    #!/bin/bash

    cd /PATH/TO/ROMPR
    php ./romonitor.php --currenthost:Default --player_backend:mpd >/dev/null 2>&1 &

You need to make some changes to that:

* **/PATH/TO/ROMPR** is the full path to your RompЯ installation. Refer to the installation instructions for more details.
* **currenthost:** should be followed by the name of one of the Players as displayed in your Configuration menu.
* **player_backend:** should be followed by either mpd or mopidy, depending on the type of player.

Now you just need to run this script.

    cd /directory/where/you/put/romonitor.sh
    chmod +x romonitor.sh
    ./romonitor.sh
    
The script will exit immediately but it will leave the romonitor program running. You can check by typing

    ps aux | grep romonitor
    
And you should see something like

    bob       1336  0.0  1.0  63572 19572 ?        S    13:45   0:00 php ./romonitor.php --currenthost:Mopidy --player_backend:mopidy
    bob       2828  0.0  0.0   4696   804 pts/0    S+   14:02   0:00 grep --color=auto romonitor

### If you're using Multiple Players

In the case where you're using [multiple players](/RompR/Using-Multiple-Players) you'll need to create a separate shell script for each player that you might access using a mobile device. These must run on the system where RompЯ is installed.

### Loading at startup

To make sure romonitor gets loaded every time you boot, you can just add your shell script as a login program, using whatever method your choice of desktop environment provides to do that.

### What it doesn't do

The other thing that requires the device to be awake is populating Personalised Radio stations. Currently the only solution to this is to wake your device up every so often. It doesn't have to be after every track; even if the Current Playlist runs out of tracks, waking your device will repopulate it.

### Troubleshooting

If it's not working you can run the php script direct from a terminal to see the error output. To make sure you see all the output, first enable [debug logging](/RompR/Troubleshooting) to at least level 7 and make sure you are not using a custom log file. Then go to a terminal and type

    cd /PATH/TO/ROMPR
    php ./romonitor.php --currenthost:Default --player_backend:mpd
    
(remembering to change the values as appropriate).

You'll see any php error messages along with debug output from romonitor. If you're filing a bug you need to include this information.

If you enable debug logging before you run romonitor via the shell script, you will see romonitor's debug output in your web server's error log (or your custom log file) but you won't see any PHP error messages, so running via the terminal is a good start.
