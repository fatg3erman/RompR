# The Music Collection

The Music Collection is the reason RompЯ exists. It was designed to sort your music by artist and album, even if they're not correctly tagged.

![](images/collectionnew.png)

For mpd users. the Collection consists of your mpd library. For Mopidy users the Collection can be created from a combination of your Mopidy backends, and tracks can be added on the fly from Spotify, Soundcloud, and other online sources. With Mopidy it creates a complete list of all the music you listen to from any source, all sorted by artist and album, all in one place.

You can choose to display the collection sorted by

* Artist (in alphabetical order)
* Album (in alphabetical or date order)
* Albums (by Artist) displays all albums at once sorted first by Artist, then either alphabetically or by date

You can choose not to apply Date Sorting to 'Various Artists' - where sorting albums alphabetically is almost always more useful.

You can choose also to only display tracks and albums that were added within a specific time period (today, this week, this month, or this year)

## Tagging And Rating

Tracks in the collection can be given a rating (from 1 to 5 stars) and arbitrary text tags. The tags and ratings will be shown in the Music Collection.

![](images/taggedtrack1.png)

They will also be shown in the Now Playing area. To rate a track that is playing, just click on the stars. To add a tag click on the + sign. To remove a tag, hover over the tag and click the x that appears.

You can also add tags and ratings from the File [information panel](/RompR/The-Info-Panel). For people on touchscreen devices, this provides an easier way to remove tags,

![](images/taggedtrack2.png)

You can search for tags or ratings using the [Search Panel](/RompR/Searching-For-Music). You can also manage them and get a sorted list by using the [Ratings and Tags](/RompR/Managing-Ratings-And-Tags) panel. Tags and ratings can also be used to generate [Personalised Radio](/RompR/Personalised-Radio).

## Collection Sources (Mopidy Only)

![](images/buildcollectionfrom.png)

For Mopidy users, the Configuration panel gives you the choice of which Mopidy backends you want to use to build your collection.

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

### On-The-Fly Collection Building

Mopidy users also have the option to add tracks to the collection as they play. If you're playing a track from, say, Spotify and you like it, just give it a tag or a rating and it will be automatically added to your Collection. It will appear in the Collection with a cross next to it, which you can click to remove the track from the Collection. Spotify albums from the [Current Playlist](/RompR/The-Playlist), the [Spotify Info Panel](/RompR/The-Info-Panel), and [Music Discovery Sources](/RompR/Music-Discovery) can also be added directly into the Music Collection.

If you're listening to an internet radio station and you hear a track you like, tagging or rating that will make RompЯ search for it on Spotify and add it to the collection if it finds it, or to your [Wishlist](/RompR/The-Wishlist) if it doesn't.

### Preferring Local Music

This option is best explained by an example. Suppose you have added some tracks from Spotify into your Music Collection on-the-fly but you then decide you'd like to buy copies of those files and add them to your local music in Mopidy. Normally, Rompr regards albums from different sources as different albums, so if you udate your Music Collection you'll have 2 copies of the album - one from Spotify and one Local. However, if you enable 'Prefer Local Music to Internet Sources', the pre-existing Spotify album will be replaced with your new Local album, and all ratings, tags, and playcounts transferred to it.

Note that if the album exists in your Spotify Playlists or 'Your Music' and you are building your Collection from those sources, it will remain in your Collection. The replacement option only applies to files that have been added on the fly.


## Composers

Classical music lovers (or lovers of other genres) also have the option to sort by Composer for specific Genres. Note that this relies on tracks being tagged with Composer information, which is not always the case with some backends in Mopidy. For local files you will need to tag them yourself for this to work.

![](images/composersort.png)
