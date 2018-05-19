# Using Multiple Players

RompЯ supports using multiple players, which can be on different computers. You could have one in each room of your house to create a multi-room audio setup.

All the players can be used simultaneously, but they will not play in sync. The idea is that a person in one room can use one player and a person in another room can use a different player. All the players share the same Music Collection so Playcounts, Podcasts, Tags, Ratings, etc are shared across all of them.

## Defining Players

You can add new players from the configuration menu. This opens a dialog box.

![](images/players.png)

The Name can be anything that is meaningful to you.

Remember that 'localhost' in a player definition means 'the computer running the web server'.

If you're using players that are on different computers from the web server you should also read [this guide](/RompR/Troubleshooting).

## Selecting Players

To select a player to use, just select it in the Configuration menu and that browser will use it whenever you open RompЯ.

![](images/players2.png)

## Limitations

* All players must be the same type - i.e all MPD or all Mopidy. This is because MPD and Mopidy use different and incompatible URI schemes for files.
* If you're using local music, all players must have the same music files stored in the same directory path. The easiest way is to put your music on a network share somewhere.
* Do NOT try to control multiple players from multiple tabs in the same browser. I cannot stop this or detect it and it WILL result in data corruption.
