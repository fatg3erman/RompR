# Using Multiple Players

RompЯ supports using multiple players, which can be on different computers. You could have one in each room of your house to create a multi-room audio setup.

All the players can be used simultaneously, but they will not play in sync. The idea is that a person in one room can use one player and a person in another room can use a different player. All the players share the same Music Collection so Playcounts, Podcasts, Tags, Ratings, etc are shared across all of them.

## Defining Players

You can add new players from the configuration menu. This opens a dialog box.

![](images/players.png)

Click the + icon to add a new player.

The Name can be anything that is meaningful to you.

Remember that 'localhost' in a player definition means 'the computer running the web server'.

If you're using players that are on different computers from the web server you should also read [this guide](/RompR/Troubleshooting).

## Selecting Players

To select a player to use, just select it in the Configuration menu and that browser will use it whenever you open RompЯ.

![](images/players2.png)

On the Phone and Tablet skins, you can also select players from the Volume Control dropdown

![](images/players3.png)

When switching players you will be given the option to transfer your Current Playlist to the new Player. Selecting 'Yes' will do this and playback will continue from the point where it left off.

![](images/players4.png)

## Limitations

* All players must be the same type - i.e all MPD or all Mopidy. This is because MPD and Mopidy use different and incompatible URI schemes for files.
* Do NOT try to control multiple players from multiple tabs in the same browser. I cannot stop this or detect it and it WILL result in data corruption.
* If you're [logged in to Last.FM](/RonpR/LastFM), the same Last.FM user is used across all Players.

## Local Music Databases

If you're using local music, all players must have the same music files stored in the same directory path. The easiest way is to put your music on a network share somewhere.

### With MPD

MPD's local music database must be kept in sync across all your players. With mpd you can try to set the auto_update flag in mpd.conf, although I haven't been able to test whether this works on network shares.

If this doesn't work for you, you will have to use 'Update Music Collection Now' on all your Players if you add or remove local files.

### With Mopidy

With Mopidy, one solution to this is to use mopidy-local-sqlite and put the database on a network share where all the players can access it. However this can be very slow to load when you start Mopidy.

Another is to use mopidy-beets instead, with one centralised Beets server.

However, the best solution is to use 'Slave' Mode on all but one of your Players. This relies on you having Mopidy's 'file' backend installed, which is enabled by default in all recent versions of Mopidy. The file backend does not require Mopidy's local music database, so it can be used to add local music.

#### Using Slave Mode

Decide on one Player to be your 'Master' Player. This will be the one on which you Update your Music Collection. The local files database can be stored locally on this Player, the others do not need to have access to it.

Then enable the 'SLAVE' option on all your other Players, as in the screenshot above. You must not update your collection when using any of the Slave Players.

You must also set the path to your local music files as described for [Album Art](/RompR/Album-Art-Manager).

Now when you add a local track to one of your Slave Players, Rompr will automatically use the file backend instead of the local backend. All of Rompr's other functions - including Playcounts, Tagging, and Rating - will continue to work as normal.

## Suggested Setup

All that was rather complicated, so here's an example setup.

* One computer in room 1, running Mopidy and RompЯ.
    * Music is stored on an external USB drive which is mounted on /media/USBDrive.
    * /media/USBDrive is shared on the network

* Another computer in room 2, running Mopidy
    * The shared /media/USBDrive from above is mounted on /media/USBDrive
    * This Player is configured as a Slave

With this configuration, all your data is kept in sync and all Players can play your local music. Further players can simply copy room 2.
