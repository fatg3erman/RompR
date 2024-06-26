# Using Multiple Players

RompЯ supports using multiple players, which can be on different computers. You could have one in each room of your house to create a multi-room audio setup.

All the players can be used simultaneously, but they will not play in sync. The idea is that a person in one room can use one player and a person in another room can use a different player. All the players share the same Music Collection so Playcounts, Podcasts, Tags, Ratings, etc are shared across all of them.

## Multiroom Audio

If you want to have multiroom audio with all rooms playing the same music, in sync, then RompЯ has full support for controlling [Snapcast](/RompR/snapcast)

## Defining Players

You can add new players from the configuration menu. This opens a dialog box.

![](images/playerdefs.png)

Click the ADD to add a new player.

You can edit the player name, the name can be anything that is meaningful to you.

Remember that 'localhost' in a player definition means 'the computer running the web server'.

If you're using players that are on different computers from the web server you should also read [this guide](/RompR/Troubleshooting).

### Webscokets

Websockets are an *additional* interface to the Player that RompR can use.

#### With Mopidy

With Mopidy, this is what is referred to as the HTTP Frontend.
RompR can use [Mopidy's HTTP frontend](/RompR/Rompr-And-Mopidy#Using-the-HTTP-Frontend-for-Improved-Responsiveness) to make the UI
more responsve, to query Mopidy for Album Art, and for doing searches (this is more efficient than using the MPD frontend). You just need
to make sure you have the HTTP frontend enabled in Mopidy and set the port number correctly in RompR.

#### With MPD

MPD does not have an HTTP Frontend, but RompR can [fake one](/RompR/Rompr-And-MPD) using the Websocket port number you supply here.
**If you are using multiple MPD Players, the websocket port for each one MUST be different.**
When you refresh the browser RompR will start its websocket server for the Player you are connecting to.

#### Verifying the Websocket Connection

You will see a connection notification that has two port numbers in it, for example 'Connected to MPD at localhost:6600/8001'.
(If you're using a UNIX socket for MPD you will see 'Connected to MPD at ip.address.of.mpd/8001')

![](/images/mpd_on_ws.png)

If you don't get the port number after the / then the websocket server failed to start. If you're using a UNIX socket to connect
to your player you will see only one port number and that will be the Websocket port. If you don't see the websocket port the
websocket is not working.

If you get a permanent message saying 'Player has stopped responding' this means the backend (web server) is able to connect to the MPD interface
but your browser is not able to connect to the Websocket. If you get problems, try not using 'localhost' in your player definition and refresh
the browser.

If Websockets are not working for you, you can disable them by leaving the Websocket value blank.


## Selecting Players

To select a player to use, just select it in the Configuration menu and that browser will use it whenever you open RompЯ.

![](images/players2.png)

On the Phone and Tablet skins, you can also select players from the Volume Control dropdown

![](images/players3.png)

When switching players you will be given the option to transfer your Play Queue to the new Player. Selecting 'Yes' will do this and playback will continue from the point where it left off.

![](images/players4.png)

You can also choose a player by adding a 'currenthost' parameter to the URL, eg

	http://my.rompr.server/rompr?currenthost=OneOfMyPlayers

this permits you to store a bookmark for each player you use, or add an icon for each player to the home screen of your phone, for instance.

## Limitations

* Do NOT try to control multiple players from multiple tabs in the same browser. I cannot stop this or detect it and it WILL result in data corruption.
* If you're [logged in to Last.FM](/RonpR/LastFM), the same Last.FM user is used across all Players.

## Local Music Databases

If you're using local music, all players must have the same music files stored in the same directory path. The easiest way is to put your music on a network share somewhere.

### With MPD

MPD has a 'satellite' players feature that you can use for this purpose. See the MPD documentation.

### With Mopidy

With Mopidy, one solution to this is to use mopidy-local-sqlite and put the database on a network share where all the players can access it. However this can be very slow to load when you start Mopidy.

Another is to use mopidy-beets instead, with one centralised Beets server.

However, the best solution is to use 'Remote' Mode on all but one of your Players. This relies on you having Mopidy's 'file' backend installed, which is enabled by default in all recent versions of Mopidy. The file backend does not require Mopidy's local music database, so it can be used to add local music.

#### Using Remote Mode

Decide on one Player to be your main Player. This will be the one on which you Update your Music Collection. The local files database can be stored locally on this Player, the others do not need to have access to it. You will not be allowed to update the Music Collection when using any of the Remote Players.

Then enable the 'REMOTE' option on all your other Players, as in the screenshot above.

You must also set the path to your local music files as described for [Album Art](/RompR/Album-Art-Manager).

Now when you add a local track to one of your Remote Players, Rompr will automatically use the file backend instead of the local backend. All of Rompr's other functions - including Playcounts, Tagging, and Rating - will continue to work as normal.

## Suggested Setup

All that was rather complicated, so here's an example setup.

* One computer in room 1, running Mopidy and RompЯ.
    * Music is stored on an external USB drive which is mounted on /media/USBDrive.
    * /media/USBDrive is shared on the network

* Another computer in room 2, running Mopidy
    * The shared /media/USBDrive from above is mounted on /media/USBDrive
    * This Player is configured as a Remote

* All Mopidys must have the same backends installed and configured.

With this configuration, all your data is kept in sync and all Players can play all your  music. Further players can simply copy room 2.

## Using Different Player Types

**EXPERIMENTAL AND LIMITED FEATURE. Use at your own risk**

There is now some support for mixing Mopidy and MPD. You can have some players as MPD and some as Mopidy but there are several caveats:

* You can only update your Music Collection when you are using the same type of player that was used to create it.
* You must be using Mopidy's 'local' (or 'local-sqlite') backend for your local files. Using 'file' is not supported.
* Obviously, attempting to add tracks from online services to MPD will not work.
* Transferring the Play Queue between players will work, but only if all tracks are local or streams. If the Play Queue contains tracks that cannot be played by MPD and you switch to MPD, the results will be unpredictable.
* Any MPD players will need to keep their local music database updated. If you use a Mopidy Player to create your Music Collection (and you should), then you will need to use another MPD client for this (for example, Sonata).
* All players must have their local tracks mounted on the same path, as described above.
* Adding tracks to stored MPD playlists is not supported in this configuration

**Supported Features**

* Playback of local files on all players
* Playback of Podcasts and Radio Stations on all players
* Transfer of local files, radio stations, and podcasts when switching players
* Tagging, Rating, and Playcounts shared across all players
* Personalised Radio (only for local files when using MPD)


