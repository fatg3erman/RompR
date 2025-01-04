# Changelog

## Version 2.18

### If upgrading from a version before 2.00 please read the notes for version 2.00 before continuing.

* Fix bug where the 'open in new window' links were malformed in the Wikipedia info panel
* Fix bug where the first Album in a list would disappear if any of its metadata was updated while using the Phone skin
* Updated Simplified Chinese translation provided by Roll8ack


## Version 2.17

### If upgrading from a version before 2.00 please read the notes for version 2.00 before continuing.

* You can now create new Tags from the popup menus in the Collection browser
* You can now choose to play 'All Tracks Tagged With "xxxx"' for a particular Album
* Updated Simplified Chinese translation provided by Roll8ack
* The position of the icon bar on the Phone skin can now be selected as either Top or Bottom of the window. It was originally moved to the bottom because it interfered with a touch event at the top of screen on iOS, but Apple then moved that to the bottom, so the only solution is to permit users to put it where it works for them.


## Version 2.16

### If upgrading from a version before 2.00 please read the notes for version 2.00 before continuing.

* New sort modes for Genre, Tag, and Rating allowing a sub-sort by Artist.
* Permit 'Sort Albums by Date' to be applied indpendantly to the Collection and Spoken Word
* When 'Sort Albums by Date' is disabled, obey the same "ignore prefixes" rules as for Artist names
* Mostly switch from using JQuery ajax to the fetch API, which permits better error handling, and
requests to be prioritised and so makes the UI slightly more responsive, except in Firefox where priorities are not supported.
* Fix bug where the Info Panel would mostly fail to update if Musicbrainz didn't respond.
* Podcast downloads could not write ID3 tags to m4a files due to getid3 not supporting it. Added support for
writing these tags usong AtomicParsley. You should install the AtomicParsley package to take advantage of this.
* Fix for Community Radio when no DNS hostname coud be found because the local gethostbyaddr() wasn't working for some reason
* Various small bugfixes


## Version 2.15

### If upgrading from a version before 2.00 please read the notes for version 2.00 before continuing.

* Support for Mopidy-Subidy to build your collection from a Subsonic server. Note that this will be slow due to Subidy having to query your subsonic server for every single album. Also Subsonic does not seem to return Album Artists, this might cause sorting issues in your Collection.
* Simplified Chinese translation provided by Roll8ack


## Version 2.14

### If upgrading from a version before 2.00 please read the notes for version 2.00 before continuing.

* Sleep Timer fadeout time is now configurable.
* Further support for Qobuz using the [mopidy-qobuz-hires backend](https://github.com/vitiko98/mopidy-qobuz)
* New options to not scrobble Podcasts and/or Spoken Word tracks to Last.FM
* MPD outputs with hyperlnks in the name will now cause a link to be displayed in the UI (contributed by ron-from-nl)
* All Mopidy-linked functionality that depends on online music sources (eg 'Music From Everywhere') should now recognise all the main ones that work well (Spotify, YTMusic, YouTube, Qobuz)
* You can now limit your Collection view to only show tracks from a specific Mopidy backend (eg only local, only spotify, etc..)
* A few minor bugfixes


## Version 2.09

### If upgrading from a version before 2.00 please read the notes for version 2.00 before continuing.

* Basic support for Mopidy-Qobuz, provided you use the mopidy-qobuz-hires backend in Mopidy


## Version 2.08

### If upgrading from a version before 2.00 please read the notes for version 2.00 before continuing.

* Album art bugfix for people running PHP 8, contributed by svalo.
* Files can now be downloaded through the browser from the File Browser by clicking on the 'mp3' (or whatever) icon next to the file name. This works with anything in mpd and for local files in Mopidy. It requires the path to your local music to be configured in the preferences.


## Version 2.06

### If upgrading from a version before 2.00 please read the notes for version 2.00 before continuing.

* By popular request there are now seek forwards/backwards buttons next to the progress bar. Click or press once for a 10 second skip. Click, or press, and hold for an incrementing skip.
* RompR used to try to set your country code by using a web service. Sadly someone stole my API key and has been using my account so I've removed that functionality and disabled the account. RompR will now attempt to work out your contry code based on the limited information the browser gives it, which may be wrong. This might affect your ability to play Spotify tracks, so go to the preferences panel and make sure it is set correctly (you only need to do this once).
* Fix bug where Player definition upgrade would fail if mopidy_http_port wasn't set.
* Fix Info panel bug where 'Similar Artists' in the Spotify panel didn't work.
* Some minor display and layout fixes for bugs introduced in version 2.00
* Mopidy-Spotify is well on the way to working again thanks to the efforts of its maintainers, so some of the functionality I pulled out of version 2.00 is back. This includes being able to play tracks directly from the Spotify Info panel and the Discoverator, as well as the Spotify Personalised Radio stations, which have had a revamp and work better than they used to.
* All skins should now display and work correctly on touch devices and devices with mice, though drag-and-drop is only supported on desktop browsers. Laptops with touchscreens should repond to touch events on all elements, but whether drag-and-drop works on a laptop touchscreen will depend on your OS and browser. I don't have one to test it out on, if anybody does please tell me how well it works.
* On devices with only touchscreens all clickable elements now respond when you remove your finger from the screen instead of the old behaviour which was to use the browser's emulated mouse-click - this required you to 'jab' at the icons which many people found difficult.
* Better Music Collection handling of Classical Music. See [The Docs](/RompR/Music-Collection#Classical)
* New 'All Albums at Random' in Personalised Radio
* Fixed bug where setting a 'Number To Keep' for a podcast would cause the refresh to fail


## Version 2.00

### Big Changes In This Version. Please Read The Following Before Updating

* This bump in version number reflects a big change in how RompR works internally. This has allowed me to improve a lot of the functionality, but I cannot possibly test it on every system.
* **You must be running RompR version 1.40 or newer to upgrade to this version**
* **It is strongly reccommended that you [back up your entire database](/RompR/Backing-Up-Your-Metadata#Backing-Up-Your-Entire_database) before updating to this version, as if it does not work for you then rolling back will be impossible without a database backup.**
* **In order to upgrade to this version from an earlier version you must delete everything from your installation except your prefs and albumart directories, then copy the new version in.**
* If you do have problems after upgrading, please [raise a bug on the issue tracker](https://github.com/fatg3erman/RompR/issues) and I will attempt to fix it or assist you.
* This version introduces the [RompR Backend Daemon](/RompR/Backend-Daemon) which replaces romonitor and is now a requirement. The Daemon performs some tasks that are very difficult to do in the browser but very easy to do if you have a process running permanently on the server. It requires a POSIX operating system and therefore RompR is no longer supported on Windows. RompR will, on most systems, start this daemon itself so you shouldn't need to do anything *except* if you were previously running romonitor, in which case you **must** read the link above.
* Alarms and the Sleep Timer no longer require a browser to be open, and are therefore now supported in the Phone skin. As a result of this change though, you will need to recreate any Alarms you had previously configured.
* There is now a [Websocket Server](/RompR/Rompr-And-MPD) that makes the UI more responsive when you're using MPD - essentially it mimics the part of Mopidy's HTTP interface that RompR uses. It's not required but it is recommended. There are some pre-requisistes you need for this to work, please read the link.
* The websocket port to use for Mopidy is now configurable for each player, to help those who use multiple players running on the same machine. Crazy people :) As a result of this though if you do have multiple players you might find that the websocket port number is wrong after upgrading. It's easily fixed from the config panel.
* Unified Search - everything you can search for in RompR is now available through the main Search Panel instead of being spread out in different places throughout the interface.
* IceCast radio support has been removed, because the "API" really sucks and the station IDs keep changing.
* Fix bug where album art might be partially downloaded when using MPD (Fix contributed by corubba)
* New option to not clear the Play Queue before starting personalised radio.
* Try to make RompR properly timezone aware, so the alarm clock works when Daylight Saving Time is enabled, for example. RompR will try to work out your timezone, but if you notice the alarm clock isn't going off at the right time you should set date.timezone in your php.ini.
* Done some work to make the Desktop and Skypotato skins work better on touch-enabled devices like tablets:
	* It should auto-detect a touch interface and enable swiping and long press on the Play Queue.
	* On a touch device, you can pinch to shrink or enlarge the left-hand list (ie the list of artists) in both skins, and also the Play Queue in the desktop skin.
	* On a touch device it should now use the device's native scrolling instead of RompR's custom scrollbars.
* A new button in the preferences panel allows you to save the current settings as defaults for all browsers. This will assist people who prefer to run in private sessions - your UI settings will be saved to the backend when you push this button. This includes the skin and single/double click mode but some prefs that are stored as Cookies are not saved - this includes the current Player as well as some of the Collection sort options.
* People who use phones with silly rounded corners can now specify a padding to be applied to the bottom of the Phone skin so that the rounded corners or other bits that Apple decide to put in our way don't obscure my UI.
* Clients in a Snapcast Group can now have their volumes locked together, so that adjusting one adjusts them all. So you can now set the relative volumes as you want them, then make the whole lot louder if you need to.
* Make the SQLite collection case-insensitive, which makes it work the same way as MySQL and means I can remove a lot of case checking statements, which speeds things up.
* All tracks and podcast episodes can now have an arbitrary number of named bookmarks associated with them.
* All Personal Radio stations are now populated by the Backend Daemon, so there is no longer any need to keep a browser open.
* If Mopidy's HTTP interface is available and you do a search in RompR and limit the search to specific backends the search can be performed using Mopidy's HTTP interface instead of the MPD interface. This can provide significantly improved search performance and better quality information but it can use a lot of RAM and in certain setups it might be a lot slower. You can disable this behaviour by unchecking 'Use Mopidy HTTP interface for Search' on the rompr/?setup screen.
* The usual collection of undocumented bugfixes.
* **Youtube Music as a Spotify Replacement**
* Now that Mopidy-Spotify is no longer working, and we don't know how long it will take (or if ever) to get a fix, the rompr/?setup screen has an option to mark all your Spotify tracks as unplayable. They will no longer appear in your collection and will be added to Your Wishlist. Note that selecting this option will force a rescan of your Music Collection. The Wishlist Viewer will give you an easy way to browse them and decide which ones you want to buy digital or physical copies of. I recommend Bandcamp, where the artist gets a fair share of the money, unlike from Spotify. If you have enabled Youtube Music support in Mopidy the Wishlist Viewer will permit you to search for tracks on Youtube Music and import them into your Collection in place of your Spotify tracks, preserving the tags, ratings, and playcounts.
* For YouTube Music support in RompR you can use Mopidy-Youtube with musicapi_enabled set to true, or Mopidy-YTMusic 0.3.8 or later. Mopidy-YTMusic is preferred for Youtube Music support because it handles artists, albums, and tracks whereas Mopidy-Youtube regards everything as either a playlist or a video. Using both backends together gives you access to all of Youtube and Youtube Music.
* For Mopidy users, all Personalised Radio stations that relied on Spotify support have been removed.
* The other Mopidy-specific Personal radio stations have been adjusted so that they work with Mopidy-Youtube and Mopidy-YTMusic.
* Where the Info Panel used to show lists of Spotify albums and allow you to play them it will still do this but now if you try to play tracks from them it will search for them using all your online sources and play whatever it can find. You can still add these albums to Albums To Listen To as well. You can also now add albums from Youtube that come up in search results to Albums To Listen To.


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
