# Using RompЯ with Mopidy

[Mopidy](http://www.mopidy.com/) is an mpd-like player that does everything mpd does and more. It plays Spotify, for one thing :)

If you use Mopidy, please make sure you read the following to ensure you get the best out of it.

## Communication with Mopidy

RompЯ communicates with mopidy using its MPD frontend - you must have mopidy-mpd installed.
Mopidy version 1.1 or later is required.

In mopidy.conf, your mpd section needs to contain

    [mpd]
    connection_timeout = 120

120 is a minimum (it's in seconds). If you have a large music collection try a much larger number, say 600.

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

## Google Play

If you have a Google Play Music subscription, then [mopidy-gmusic](https://github.com/mopidy/mopidy-gmusic) will allow you to search all of Google Play's online music library in the same way the Mopidy-Spotify allows you to search Spotify. You'll also be able to make use of many of RompЯ's music discovery features. If you don't have a Google Play Music subscription then you'll only be able to play tracks you have uploaded to your Google Music library, which makes the Music Discovery features much less useful, but they'll still be presented to you as I've no way to know.

## Scanning Local Files

Where MPD provides an 'update' command that RompЯ can use to update MPD's music database, Mopidy does not and so RompЯ can not easily make Mopidy scan local files - this has to be done with the 'mopidy local scan' command, which cannot be run directly by RompЯ .

The only current solution to this to run mopidy local scan yourself first. If you're using the json local files backend in mopidy you will then need to restart mopidy. You do not need to restart mopidy if you are using mopidy-local-sqlite.

## Genres

Note that only Mopidy-Local seems to return Genres, so Genre-based Collection functions will not work as your might expect if you use Spotify, Soundcloud, etc.