# Personalised Radio

Your listening habits, tags, and ratings, can be used to generate dynamic playlists on-the-fly.

Users of MPD and Mopidy also get a host of options to generate these playlists from your Music Collection.

Mopidy users get a host of extra online Music Discovery stations, that are described [here](/RompR/Music-Discovery).

![](images/personalradio.png)

These should all be self-explanatory. You can choose to play by Tag or Rating, or other criteria.

When you start one of these stations playing, 5 tracks will be added to the Current Playlist, a header will appear at the top of the Playlist, and Consume mode will be enabled (and cannot be disabled while the station is playing).

![](images/personal2.png)

You can skip, pause, stop, etc as normal. When a track has finished it will be removed from the Playlist and a new one will be added. You can add tracks to the end of the playlist (or any other position) and re-order the playlist as normal. New tracks will only be added automatically when there are less than 5.

To stop the dynamic playlist generation and return to normal mode, either click the x next to the header, clear the playlist, or refresh the browser window.

## Mobile Devices

When using a mobile device to run RompЯ, you should understand that the browser must be open for new tracks to keep being added. If the device sleeps (screen turns off) then the playlist will stop updating until you wake the device. It's worth mentioning too that Playcounts will not be updated and tracks will not be scrobbled if your device sleeps.
