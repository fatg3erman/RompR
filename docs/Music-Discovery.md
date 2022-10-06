# Music Discovery

RompЯ contains many features to help you discover new music based on your listening habits. These will work best if you are using RompЯ with Mopidy and a Spotify Premium subscription and I'll be assuming that is the case. If you don't have Spotify then you can still get suggestions of albums you might like but you will not be able to add tracks to the playlist.

## More Personalised Radio

The Personalised Radio panel includes more radio stations designed to help you discover music - most of these are only present for people using Mopidy with Spotify.

### Music From Everywhere

![](images/musicfromeverywhere.png)
This panel allows you to make use of all your Mopidy sources - Soundcloud, Youtube Music, etc to create playlists. Most of the stations here will work with Mopidy-Spotify, Mopidy-YTMusic, and Mopidy-Youtube

The Last.FM stations require you to be [logged in to Last.FM](/RompR/LastFM). They use your Last.FM scrobbles and Last.FM's suggestion engine to create playlists based on your listening over the past week, month, year, or all time. 'Lucky Dip' may produce a more varied selection than 'Mix'.

Favourite Artists will play a selection of tracks by artists determined to be your Favourites based on your listening habits.

Genre requires Mopidy-Spotify because no other backends can search by Genre.

Tracks by Artist will play a random selection of tracks by the artist you enter.

### Note on Mopidy-YTMusic and Mopidy-Youtube

The Music From Everywhere stations will search your Mopidy sources for music to play.

If you're using Mopidy-YTMusic and you're not a paid subscriber it wil work but some tracks will not play. Also tracks from
Mopidy-YTMusic cannot be added to the Music Collection because Mopidy-YTMusic cannot accept a URI it has not seen before. If you attempt to add a YTMusic track to the
Collection it will instead be added to the Wishlist.

If you're using Mopidy-Youtube these stations work best if you enable the Music API. See the Mopidy-Youtube documentation for how to do that.

### Music From Spotify (Disabled Due to no Spotify Support in Mopidy)

![](images/musicfromspotify.png)
The first three options (Spotify Weekly Mix, Spotify Swim, and Spotify Surprise!) use your listening habits to create dynamic playlists. Weekly Mix is based on your listening habits over the past week. The other two use successively wider criteria to select tracks.

Favourite Artists and Related Artists calculates your favourite artists based on number of listens and ratings and then plays you tracks by those artists and by other artists that Spotify says are 'related' - which should mean they're of a similar genre or style.

Finally you can type an artist name into 'Artists Similar To' to get tracks from artists that Spotify says are similar to that artist.

### Create Your Own Spotify Playlist Generator (Disabled Due to no Spotify Support in Mopidy)

![](images/rollyourown.png)
This is a Spotify feature that I found one day while browsing their documentation.

First enter up to five genres, which must be chosen from the drop-down list. Then you can drag both ends of the sliders to set a range of values for each of the parameters. Eg if you want 'High-Energy' tracks, drag the left-hand end of the 'Energy' slider to the right.

What these parameters actually mean, only Spotify really knows. They have algorithms that determine what these values are for each track but they won't say how they work.

The best thing with this panel is to experiment. If you find a group of settings that provide pleasing results you can Save them, they will then appear as a new option under 'Music From Spotify', as you can see in the example image above - click the x to remove them.

## Listening to the Radio

When listening to internet radio, if the station you're listening to provides Artist and Title information, this will be displayed in the now-playing panel. If you hear a track you like you can give it rating or a tag. RompЯ will then search for it on Spotify and will add it into your collection if it finds it. It the track is already in your collection, the rating or tag will be added to that track instead.

If the track can't be found it will be added to your [Wishlist](/RompR/The-Wishlist)

## The Info Panel

Mopidy-Spotify users can get more suggestions about new music related to the currently playing track by using the Spotify [Info Panel](/RompR/The-Info-Panel)
