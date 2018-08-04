# Versions

This is not a complete list of changes and it only starts with version 1.14.
Note that some versions listed here may be unreleased, I use version number incremements for testing purposes so released version numbers may not be contiguous.

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
* Option to transfer current playlist to new player when switching players
* New Global Refresh, Mark as Listened, etc controls for podcasts
* Upgrade to jQuery 3, which might make some older browsers no longer work
* Added Slave mode for Mopidy Players so you only need to have one 'Master' Mopidy Player and all the others can still play local tracks using the file backend without the need to have a local files database on all of them
* Partial, experimental, support for using mixed player types (MPD and Mopidy) as Players.
* Several UI improvements to click handling and tooltips to reduce memory footprint and CPU usage
* Updated Russian translation by Паша

## Version 1.19

* Fixed bug where plugins menu didn't work in Tablet skin
* Added two new icon themes
* Some code refactoring and minor UI bugfixing
* Added swipe and long press options to phone skin to allow tracks to be removed from and re-ordered within the Current Playlist
* Added a new button to allow the Current Playlist to be re-ordered on the Tablet Skin
* Moved Google API Credentials fields to the setup screen - they're messy, technical, and don't really want to be exposed to all and sundry.
* Added Debug Information plugin to help with assisting in bug reports.
* Added play controls to top bar on wide screens (>799px) on Phone and Tablet skins
* Fix bug where Playcounts could be lost if the browser was refreshed while a track was playing
* Added default options for new Podcasts
* Added option to mark 'New' podcast episodes as 'Unlistened' before refresh. This used to be the default behaviour. The default is now that episodes will remain as 'New' until a new one is published. Rompr therefore accurately reflects the state of the podcast's feed.
* Improved podcast refresh intervals. 'Weekly' and 'Monthly' now actually mean what they say (same time and day of the week) and will attempt to set their refresh time based on the peridocity of the episodes in the podcast, to try to ensure the refresh happens automatically when a new episode is published.
* The Current Playlist and the Subscribed Podcasts display now use thumbnail album covers instead of full resolution ones scaled down. This should save a lot of memory which may help users on mobile devices.
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
