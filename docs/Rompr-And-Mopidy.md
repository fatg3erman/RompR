# Using RompR with Mopidy

Mopidy (http://www.mopidy.com/) is an mpd-like player that does everything mpd does and more. It plays Spotify, for one thing :)

If you use Mopidy, please make sure you read the following to ensure you get the best out of it.

## Communication with Mopidy

RompЯ communicates with mopidy using its MPD frontend.
Mopidy version 1.1 or later is required.

## If you use Mopidy-Beets

It's quite good, isn't it? You need to make sure that your browser can access your Beets server if you want to get additional File Information and Lyrics in RompR. If your browser runs on a different computer than your beets server, then your beets config.yaml needs to contain

    web:
      host: IP.address.of.beets.server

Otherwise beets will not allow RompR to talk to it.

Amongst the other benefits, apart from Lyrics, are that if you used Beets' album art extension to download album art when you tagged your files, this will be available to RompR automatically.


## If you use mopidy-local-sqlite

There seems to be a bug in the scanner engine in mopidy's sqlite backend where sometimes it puts tracks on the wrong albums. Putting the following in your mopidy configuration seems to work around this.

    [local-sqlite]
    enabled = true
    use_album_mbid_uri = false
    use_artist_sortname = false

## Building Your Music Collection

The configuration panel will allow you to choose various sources from which to build your Music Collection (Local Files, Spotify, SoundCloud etc).
If you don't want to build a collection this way, tracks from anywhere can be added to the collection by tagging or rating them at any time.


Tagging or rating a track that is playing on a radio station will make RompЯ search for it on Spotify (if you have Spotify) and add it to your collection if it can find it, or to your wishlist if it can't.


Note that if you use local files, RompЯ cannot get mopidy to scan them because mopidy does not support that. You will therefore have to run "mopidy local scan" yourself, then restart mopidy before you update the collection from rompr. (If you use the sqlite backend in mopidy you do not need to restart it)
