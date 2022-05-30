# Changelog


## Version 1.62
* **It is strongly suggested that you back up your database before updating to this version, and also make a Metadata Backup.**
* This version introduces the [RompR Backend Daemon](/Backend-Daemon) which replaces romonitor and is now a requirement.
The Daemon performs some tasks that are better not left to the browser. It requires a POSIX operating system
and therefore RompR is no longer supported on Windows. RompR will, on most systems, start this daemon itself so you
shouldn't need to do anything *except* if you were previously running romonitor, in which case you **must** read the link above.
* Alarms and the Sleep Timer no longer require a browser to be open, and are therefore now supported in the Phone skin.
As a result of this change though, you will need to recreate any Alarms you had previously configured.
* Fix bug where album art might be partially downloaded when using MPD (Fix contributed by corubba)
* The usual collection of undocumented bugfixes.
* Make the SQLite collection case-insensitive, which makes it work the same way as MySQL and means I can remove a lot of
case checking staements, which speeds things up. Note that in SQLite case-sensitivity only works with ASCII characters.
I think MySQL is better at this, but I haven't checked.
* All tracks and podcast episodes can now have an arbitrary number of named bookmarks associated with them.
* Make Spotify Playlist Collection building work again. For some reason the code worked on PHP7 but PHP8 just disappears into hyperspace
and doesn't throw any errors. I note that I did this the week before Spotify switched off libspotify support,
rendering this fix (and Mopidy-Spotify) useless.
* Fix bug where Spotify tracks restored from a metadata backup would wrongly be classed as local.
* Now that Mopidy-Spotify is no longer working, and we don't know how long it will take (or if ever) to get a fix,
the rompr/?setup screen has an option to mark all your Spotify tracks as unplayable. They will still appear in your collection
but will not be selectable, and will not be selected by Personalised Radio stations. You can use the Unplayable Tracks plugin
to view all your Spotify tracks, which will give you an easy way to browse them and decide which ones you want to buy digital or physical copies of.
I recommend Bandcamp, where the artist gets a fair share of the money, unlike from Spotify.


## Version 1.61
* Due to a change in the way I have to create cookies, mandated by Firefox, you need PHP 7.3 or later to run this version of RompR.
* Improve Metadata backup so it now restores all data including Podcasts and Radio Stations.
* Improve Themes to make them easier to edit. Add two new ones. in the process I decided to delete
some of the old ones as they were ugly and I couldn't be bothered to update them.
* Major changes to the CSS and UI code, especially in the Phone skin.
* 5 skins is too much to maintain. The Tablet and Fruit skins have been removed. If you update from an earlier version
you should delete them from your installation as they will no longer function.
* The button bar on the phone skin has been moved to the bottom of the screen because having it at the top
conflicted with a built-in touch event on Safari for iPhone.
* On iOS Safari, the Phone skin will try to ensure that the browser hides the address bar because Safari always behaves as though
it is hidden even when it isn't. Except when it doesn't. It'll try to make Chrome work sensibly too, but that's even harder.
Adding RompR as an icon to your home screen is the best way to run it.
* Moved Players and Snapcast to the top of the Prefs panel, since the Background Images panel now means you
had to scroll a long way down to reach them.
* 'Play From Here' option on the track popup menu, to mimic what CD Player Mode does when not in CD Player Mode.
* Fix a slew of issues caused by an "upgrade" to PHP 8.1 which has suddenly deprecated loads of stuff and started
throwing fatal errors all over the place without warning, even in its internal functions. Please Stop Doing This.
* As much as possible, remove dependency on PHPQuery after PHP 8.1 broke it without warning.
* Fix Icecast so search and pagination works again
* Change all radio station browsers so you just have to click the station title to play it. Having no
extra menus makes the UI much easier to handle.
* Change Soma FM so the stream quality is a global selector.
* Set SameSite property on all Cookies to stop Firefox moaning


## Version 1.60
* I noticed an error in the installation instructions.

If you're using nginx you should check your config file. If it contains

	error_page 404 = /404.php;

You should update that so it reads

	error_page 404 = /rompr/404.php;

and then restart nginx.
* Allow crossfade to be toggled while running personalised radio (with MPD)
* Cope with Spotify search results that are simply an Artist Uri by browsing them on demand.
Currently this is not supported in the Skypotato skin unless you sort search results by Artist.
* Fix Youtube Download - if a track took more than 3 minutes to download the browser would retry and cause havoc.
* Automatically set track as Audiobook when adding certain tags
* Remove Snapcast clients from the main volume control when they disconnect
* Fix snapcast API code so it works with snapserver 0.6.0
* Don't crash if snapserver doesn't send stream metadata
* Improve handling of custom background images - new Background Image Manager shows thumbnails.
'This Browser Only' mode can be changed after upload. Random mode will cycle through every image in random order, rather
than being truly random.
* Add yet more notes about how to get MariaDB to work. NB MariaDB is proving difficult to support due to vaguaries
in version numbering and incompatibilities between it and MySQL, for which it is supposed to be a drop-in replacement.
Due to these issues I cannot guarantee that I will continue to support MariaDB. Please us SQLite unless you really really
need a remote database, in which case please try to use proper MySQL.
* Update installation instructions to provide more help for Arch Linux users.
* Various small bugfixes


## Version 1.59
* Downloaded podcast episodes can now be un-downloaded individually
* New podcast display option "New, Unlistened, and Downloaded"
* Fix OPML Importer
* Add an 'is not' option for integer values in custom radio stations
* Minor bugfixes and stuff
* Fix bug where some podcasts wouldn't refresh because their RSS feeds contained a newline before the image URL (WHY???)
* Support for MPD's 'readpicture' command, allowing MPD to read embedded album art from files, meaning the webserver does not need to have read access to your music files


## Version 1.58
* Top 40 plugin can now display Music, Audibooks, or both.
* Fixed bug where comma-separated list perferences couldn't have spaces around the commas
* Added a workaround for the long standing Mopidy bug that makes consume not work properly, since they don't seem interested in merging my fix.
This requires you to have romonitor running, [You should read the docs before enabling this option](/RompR/Rompr-And-Mopidy)
* If you're running romonitor already, you should restart it after installing this update


## Version 1.57
* 'Up Next' displayed for Audiobooks to remind you where you got to.
* Verious small bugfixes


## Version 1.56
* Fixed an issue with high memory usage if you have a lot of partially-tagged tracks. This may make this version sort those tracks a little differently,
but it should be the same behaviour as pre-1.52. In some ways I didn't want to fix this, but not doing so makes it not work for many people.


## Version 1.55
* More fixes related to running with PHP 8. If you're having issues with album art not working you'll want this version


## Version 1.53
* If you're running PHP Version 8.0 or above you'll want to install this. Otherwise it is no different from version 1.52


## Version 1.52
* Almost all the backend code has been extensively reworked to make it cleaner and more efficient
* To install this version of RompЯ over an earlier version you must *delete everything except your albumart and prefs directories* and then copy the new version into your rompr directory
* All this code reorganisation also means that **if you're running [romonitor](/Rompr-And-Mobiles) you must restart it after installing this version**
* If you're using Snapcast, RompR now uses the Snapcast JSON-RPC API so you may need to change the port you are using and make sure the JSON-RPC API is enabled in your snapserver configuration. (The default port is 1780)
* Mopidy users - if your mopidy-http interface is enabled, RompR can use it for better responsiveness and albumart
* Mopidy users - there is now an automatic way to run [mopidyctl local scan](/RompR/Rompr-And-Mopidy) when updating the Music Collection.
* Album Art Search now uses Bing instead of Google Images because Google broke my code and Bing's API is better. You'll need to [check the docs](/Album-Art-Manager) for how to get this set up
* **There is a new dependency on php-intl. You should install this package.** macOS users, if someone can figure out how to get Locale working on macOS php I'd like to hear
* Due to code reorganisation, some of the endpoints used for automation have changed:
	* The old /rompr/albums.php endpoint has moved to /rompr/api/collection/
	* The old /rompr/player/mpd/postcommand.php endpoint has moved to /rompr/api/player/
	* The old /rompr/podcasts/podcasts.php endpoint has moved to /rompr/api/podcasts/
	* Most functions that used /rompr/backends/sql/userRatings.php have moved to /rompr/api/metadata/
	* Metadata backup functions that used /rompr/backends/sql/userRatings.php have moved to /rompr/api/metadata/backup/


## Version 1.50
* Added function to download YouTube videos (when using Mopidy-Youtube) and save the audio locally for future streaming. See [here](/RompR/Rompr-And-Mopidy) as there are specific requirements for this to work.


## Version 1.48
* Seem to have missed a few
* Player selection via the URL, based on initial work by Manvendra Bhangui
* Remove some outdated terminology. You will need to restart romonitor if you are using it, but first you must refresh a browser window once.


## Version 1.42
* Added a quick-and-dirty plugin to import Ratings from Cantata into RompR. Development of Cantata has stopped, so there's very little point in putting any more work into interoperability with it.
* A few minor bugfixes.


## Version 1.41
* Added 'folder' as a valid filename for local album art
* Added Genres to the database. This permits the Collection to be sorted by Genre, along with a few other new features.
* Fix bug in Last.FM Playcount sync where it only worked the first time you used it
* Fix bug in Last.FM Playcount sync that wouldn't mark podcast episodes as listened when using MySQL
* Added Custom Personal Radio Station creator

### NOTES:
* This version will update your Music Collection the first time you run it, in order to add the Genre information to the database.
* Spotify does not return Genre information, even though the API says it does, so Genres will only work for Local Music and the data will only be as good as the state of the tags in your music files.


## Version 1.40

Thought I'd bump the version number up a bit since this seems like quite a big release that contains a lot of work.

### FEATURES:
* The first time you load this version it will update your Music Collection automatically. Sorry for having to force that on you but it's essential.
* [romonitor](/RompR/Rompr-And-Mobiles) can now do Last.FM scrobbling, which will help make scrobbles match exactly what's in the Rompr collection - which will help for those people using Last.FM to sync their playcounts across devices and helps especially with podcasts, where the metadata used by eg mpdscribble often differs enormously from the more detailed info available to Rompr.
* Make Mopidy-Youtube handling work better. This version of Rompr works best wuth the fork of Mopidy-Youtube [here](https://github.com/natumbri/mopidy-youtube)
* Completely redesigned the Community Radio Browser to simplify the interface and make it more consistent with TuneIn
* Albums can now be moved manually to the Spoken Word section, and back to the Music Collection
* New pop-up menus for all tracks allowing you to tag, rate, and add to playlists right from the Music Collection or Search Results
* The Playlist Manager has been removed, since all of its functionality is now incorporated into the main playlist chooser panel
* The Tags and Ratings manager has been removed since a) I hated it and b) almost everything it did can now be done via the main Collection panel
* The 'back' button on the phone skin is now always visible so you don't have to scroll back to the top to go back
* Done quite a lot of work on the Skypotato skin to make it neater, more efficient, and smoother and give it all the same functionality as the Desktop skin
* Almost all the UI code has been updated and tidied up for a faster, smoother UI.
* I wrote a script to automate a lot of the work in making icon themes, so now there are LOADS of them :-D
* 'Display Search Results as Directory Tree' is now incoporated into a new menu giving you the option to display your search results in a different format to the Music Collection
* A lot of images are now lazy-loaded so they're only loaded when they're visible on screen, which reduces memory usage a lot. This requires a modern browser and will fall back to loading everything at once if your browser doesn't support it. Recent versions of all major browsers should work, except for Safari where it's still listed as an 'Experimental' feature.

### FIXES:
* Translations have been cleaned up - unused translation keys have been removed. For translators, any key missing from the translations has been added to the end of the translation file, commented out, ready to be updated. This will make it much easier for you to follow my crazy and undocumented updates.
* Fix Panel hiding not working in Fruit skin
* Make the Snapcast controls look neater
* Fix some bugs relating to loading playlists
* On first run this version will upgrade MySQL installations to use 4-Byte UTF-8 encoding. Put simply, this upgrades the MySQL database to use the same character set as used by default in absolutely everything except MySQL which for some reason defauls to a 3-Byte version which makes it incompatible with basically the entire internet. As I've said before, SQLite is just better but if you insist on using MySQL/MariaDB this will help. If you have a large database this upgrade will take a very very very very very very very very very very very very long time and may well time out. If that happens make a post on the Discussion forum and I'll put up instructions on how to fix it.
* Fix ImageMagick handling of radio stations that only have a .ico file for their staion image, as many of the ones in Community Radio do. PHP-GD does not support .ico files so ImageMagick is required for these stations.
* Fixed a serious bug in the PHP URL downloading code, it's a miracle it ever worked at all.
* Fixed the play control buttons in Modern-Dark looking blurred in Safari due to Safari not rendering SVG blur correctly
* The Personalised Radio code has been re-written from scratch to make it perform better. In particular the Spotify and Last.FM stations now populate much more quickly.
* Made some changes so that Rompr is aware when a mobile device sleeps and wakes up. This reduces battery usage a little (but still can't make the alarm clock work)
* As always, hundreds of little tweaks and tinkerings here and there

### KNOWN ISSUES:
* In some circumstances, using the sort mode 'Albums (by Artist)' can cause your browser to run very slowly for around a minute after the page loads. This is not a new issue in this release but has recently been identified. I don't yet know that cause and it doesn't happen every time. Disabling your ad-blocker might help. (Rompr does not serve any ads)


## Version 1.32

* Fix last.FM Playcount importer
* Fix a small bug in Imagemagick image handling of SVG images

## Version 1.31

* Fix a bug where the collection couldn't be updated on clean installations.

## Version 1.30

* Major re-write of a lot of the backend code to streamline it and make it faster
* The default SQLite database now uses a single collection file called collection.sq3. In older releases, it would be called collection_mopidy.sq3 or collection_mpd.sq3 depending on your player type. This meant that if you had multiple players of different types you were not sharing the same collection between them all. The first time you open RompR 1.30, your collection will be upgraded to the new name. If you previously had two collections, the one that is selected will be the one that was most recently updated. Backups of the old files will be kept in prefs/oldcollections.
* [Snapcast](/RopmR/Snapcast) support
* Album Art download from Last.FM was broken, has now been fixed
* Last.FM Info panel will display artist images again, but not for 'Similar Artists', and only in certain circumstances. Last.FM have removed all artist image functionality.
* Done quite a lot of work on the Discogs Info panel to include images and a search that actually works and is quite accurate. This also makes the Videos panel work a lot better and get more accurate matches.
* Multitudinous new icon themes
* Some changes to the layout of the Phone and Tablet skins. Large tablets will now display the Now Playing, Play Queue, and Media Chooser panels simultaneously when in Landscape orientation.
* Alarm Clock and Sleep Timer now supported on the Tablet skin. Note that they still won't work if your tablet sleeps, but the tablet skin is quite good for small laptop screens so that's what this is intended for.
* Quite a lot of work has gone into preventing browsers from timing out on long collection updates. Did you know that browsers automatically retry if something takes more than 2 minutes? I didn't, but I do now. Crikey, it's annoying. So I've made some changes that will hopefully prevent this from being a problem. If you see your Collection Update just keep looping round and round, close your browser, restart your web server, then try again and send me a debug log at level 8.
* Dirble Radio Browser has been removed, since Dirble seems to be no longer working, and it was always unreliable. Now we have TuneIn and Community Radio it's not really needed.
* Various minor bugfixes

## Version 1.26

* The setting for 'Ignore These prefixes When Sorting Artists' is now applied to Podcast Title and Publisher as well.
* [Spoken Word](/RompR/Spoken-Word) support - for things like Audiobooks where you don't want them to be in your 'Music' Collection.
* A few small bugfixes
* Loading of Custom Background Images is now much faster
* Big changes to the [Alarm Clock](/RompR/Alarm-And-Sleep). You can now have multiple alarms, they can be set to repeat on specific days of the week, and can play a specific item - an album, radio station, playlist, or whatever you like. If you've been using the alarm clock, you should read the docs as everything has changed.
* The Search Panel can now be hidden, just like all the other sources panels.
* A new icon in the Now Playing area to let you add the current track to one of your Saved Playlists

## Version 1.25

* Bugfix: SmartRadio with CD Player Mode on would add entire albums
* Bugfix: Clearing playlist with Smart Radio playing would clear then repopulate the playlist due to a race condition
* Spotify Info Panel is now more likely to find a match for the artist
* The process that cleans the backend cache is now 8 times faster

## Version 1.24

* Fix bug where 'Remote' status of a player was lost if that player was selected on the setup screen
* Fix bug where Collection could be updated by a hotkey press even when the current player was a Remote
* Add Last.FM Playcount Importer and Scrobble Sync options so you can use Last.FM to sync your playcounts across devices. Read [this](/RompR/Keeping-Playcounts-In-Sync)
* There is now a setting for the number of tracks to pre-load when playing Personalised Radio
* Personalised Radio settings are remembered when you refresh the browser, so you can switch away from RompR and come back to it without losing that setting.
* Current Personalised Radio settings are now visible to all browsers connected to the player, not just the one you started it from, so it can be stopped or changed from any browser.
* If the browser that started a Personalised Radio station is closed, any other browser can take over populating it
* romonitor can now populate [certain Personalised Radio stations](/RompR/Rompr-And-Mobiles), meaning no browser is required once they have been started
* Fix romonitor bug where if you shut down romonitor it did not close its connection to the player, which would leave Mopidy in a state where it didn't respond to idle commands and so romonitor would not work when you restarted it
* Add Spotify Track Checker, to scan the Collection for tracks which have been removed by Spotify and mark them as unplayable.
* Add a plugin to view unplayable tracks and search for replacements
* Fix Last.FM bug where the love button in the Info panel didn't work
* 5 new fonts
* Many backend tidy-ups and improvements

## Version 1.23

* Fix bug where 'Use LastFM Playcounts' would reset Playcounts to zero if the response from Last.FM arrived before the response from the database, as seems to happen a lot on Windows.
* Fix 3 security vulnerabilties as found my kmille.

## Version 1.22

* Fix bug where podcast download wasn't working if you had PHP older than 7.1
* A few minor UI tweaks
* Romonitor now uses a different and more reliable method of detecting track changes. You will need to restart romonitor after you install this version.
* Fix bug where on phones you needed to tap some of the icons twice
* Add option to use Last.FM Playcounts to keep devices in sync

## Version 1.20

* Album Art now supports transparency in PNG files, and SVG files will be saved and served as SVG
* Much cleaner image handling, with automatic fallback to ImageMagick if image is not supported by GD
* Fixed typo where podcast images did not get updated
* You can now have a slideshow of custom background images, and landscape/portrait orientation is determined automatically
* Add check for badly-formatted duration strings in podcast feeds
* Orange and Cyan icon themes for better contrast against custom background images
* Added 'Albums To Listen To' feature, to keep a record of Spotify albums you've seen but haven't had time to listen to
* Added options to only display Collection tracks that were added within a specific time period, after I added something good to mine and then forgot what it was called :)
* Added more tooltips as the number of control butons spirals upwards
* The Discoverator now populates much more quickly
* Option to transfer Play Queue to new player when switching players
* New Global Refresh, Mark as Listened, etc controls for podcasts
* Upgrade to jQuery 3, which might make some older browsers no longer work
* Added Remote mode for Mopidy Players so you only need to have one 'Master' Mopidy Player and all the others can still play local tracks using the file backend without the need to have a local files database on all of them
* Partial, experimental, support for using mixed player types (MPD and Mopidy) as Players.
* Several UI improvements to click handling and tooltips to reduce memory footprint and CPU usage
* Updated Russian translation by Паша
* Fix bug where restoring a metadata backup screwed up the Wishlist

## Version 1.19

* Fixed bug where plugins menu didn't work in Tablet skin
* Added two new icon themes
* Some code refactoring and minor UI bugfixing
* Added swipe and long press options to phone skin to allow tracks to be removed from and re-ordered within the Play Queue
* Added a new button to allow the Play Queue to be re-ordered on the Tablet Skin
* Moved Google API Credentials fields to the setup screen - they're messy, technical, and don't really want to be exposed to all and sundry.
* Added Debug Information plugin to help with assisting in bug reports.
* Added play controls to top bar on wide screens (>799px) on Phone and Tablet skins
* Fix bug where Playcounts could be lost if the browser was refreshed while a track was playing
* Added default options for new Podcasts
* Added option to mark 'New' podcast episodes as 'Unlistened' before refresh. This used to be the default behaviour. The default is now that episodes will remain as 'New' until a new one is published. Rompr therefore accurately reflects the state of the podcast's feed.
* Improved podcast refresh intervals. 'Weekly' and 'Monthly' now actually mean what they say (same time and day of the week) and will attempt to set their refresh time based on the peridocity of the episodes in the podcast, to try to ensure the refresh happens automatically when a new episode is published.
* The Play Queue and the Subscribed Podcasts display now use thumbnail album covers instead of full resolution ones scaled down. This should save a lot of memory which may help users on mobile devices.
* All your Album Art needs to be updated in this version. The process will start automatically and may take a long time. There is a progress bar, and you can continue to use Rompr while it happens.
* Player selection can now be done from the volume control dropdown on Phone and Tablet skins
* Option to not display album art in the Playlist, which might help memory usage on phones, although the change to use thumbnail images will probably be enough.
* Added Categories to Podcasts
* Added a resume feature to Podcasts, so episodes can be stopped part way through and then restarted from that position.
* Added Podcast Sort Options
* Added support for PHP-GD to speed up all image handling. RompR will continue to use imagemagick if GD is not available, but you should try to install PHP-GD on your installation (sudo apt-get install php7.0-gd)

## Version 1.16

* Increase Collection Build timeout and update docs to add bigger timeout values for webservers
* Fix bug where clicking a track in a saved playlist with CD Player Mode on didn't work
* Fix bug where messy covers or the previous track's cover were displayed for albums without covers
* Fix bug where deleting a track from the Wishlist did not update the display
* Add sort options to Wishlist Viewer
* Wishlist Viewer now records the radio station that was playing when the track was added
* Some work to prevent simultaneous write operations to the database in order to prevent deadlocks
* Make podcast search work again. iTunes is sending feedURI or feedUri in its output, which is stupid
* Fix bug where adding directories to the queue did not work
* Stop enabling consume when CD Player Mode is on
* [OPML Importer](https://fatg3erman.github.io/RompR/OPML-Importer)
* Remove mopidy-scan as most distros don't permit setuid on shell scripts :(
* [Community Radio Browser](https://fatg3erman.github.io/RompR/Internet Radio)
* Custom Background Images can now be set for a specific browser instead of applying to everybody
* Custom Background Images can now be set separately for portrait and landscape orientations
* Fixes and speed improvements, especially to the backend cache which is now much faster at dealing with images

## Version 1.15.5

* Quick bugfix release as, ironically, a bug in the new version number checking code meant that clean installs of 1.15 would not start. Yes I know I jumped several minor version numbers. That happens.

## Version 1.15

* The Last.FM radio stations and adding radio tracks to the Music Collection now work with Mopidy-Gmusic.
* Russian translation by Паша

## Version 1.14

* Added [Skypotato Skin](https://fatg3erman.github.io/RompR/Skypotato-Skin)
* Made the Phone skin more 'touchy'. The old phone skin is now called 'Tablet'
* A couple of new UI themes
* Removed the Iconfont icon theme. It was getting too difficult to maintain and didn't really fit in well with the design of the UI.
* Building the Music Collection now uses about 1/10th or less of the RAM it used to use.
* Fixes for many Mopidy backends - Beets, Beetslocal, and GMusic now work properly again and you can build your collection using them
* Made the Ratings and Tags Manager much faster.
* 'Local and National Radio' now uses the Dirble radio directory, as the old listenlive links had stopped working for people outside Europe
* Added TuneIn Radio Directory browser, as Dirble doesn't seem very reliable.
* Cleaned up IceCast Radio panel so it now follows the style of the main UI, instead of being simply a modded version of the xiph.org directory web page.
* Added [romonitor](https://fatg3erman.github.io/RompR/Mobile-Devices) so playcounts still get updated while mobile devices sleep
* Added mopidy-scan so Mopidy local files can be scanned without having to do it manually
* Local Album Art embedded in Music Files can now be accessed, thanks to an updated getid3.
* When downloading Album Art automatically, Google images will now be used if nothing can be found elsewhere. However, all Google operations now require you to supply your own [API Key](https://fatg3erman.github.io/RompR/Album-Art-Manager#using-google-images-to-find-album-art)
* Updated German Translation from Frank Schraven
* Added help links to many of the UI elements, leading directly to these docs
* Many, many other tweaks and bugfixes. Almost all the code has been looked at and tweaked.
