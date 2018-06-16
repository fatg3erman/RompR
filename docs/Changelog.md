# Versions

This is not a complete list of changes and it only starts with version 1.14.
Note that some versions listed here may be unreleased, I use version number incremements for testing purposes so released version numbers may not be contiguous.

## Version 1.16

* Increase Collection Build timemout and update docs to add bigger timeout values for webservers
* Fix bug where clicking a track in a saved playlist with CD Player Mode on didn't work
* Fix bug where messy covers or the previous track's cover were displayed for albums without covers
* Fix bug where deleting a track from the Wishlist did not update the display
* Add sort options to Wishlist Viewer
* Wishlist Viewer now records the radio station that was playing when the track was added
* Some work to prevent simultaneous write operations to the database in order to prevent deadlocks
* Make podcast search work again. iTunes is sending feedURI or feedUri in its output, which is stupid

## Version 1.15.5

* Quick bugfix release as, ironically, a bug in the new version number checking code meant that clean installs of 1.15 would not start. Yes I know I jumped several minor version numbers. That happens.

## Version 1.15

* The Last.FM radio stations and adding radio tracks to the Music Collection now work with Mopidy-Gmusic.
* Russian translation by Паша

## Version 1.14

* Added [Skypotato Skin](/RompR/Skypotato-Skin)
* Made the Phone skin more 'touchy'. The old phone skin is now called 'Tablet'
* A couple of new UI themes
* Removed the Inconfont icon theme. It was getting too difficult to maintain and didn't really fit in well with the design of the UI.
* Building the Music Collection now uses about 1/10th or less of the RAM it used to use.
* Fixes for many Mopidy backends - Beets, Beetslocal, and GMusic now work properly again and you can build your collection using them
* Made the Ratings and Tags Manager much faster.
* 'Local and National Radio' now uses the Dirble radio directory, as the old listenlive links had stopped working for people outside Europe
* Added TuneIn Radio Directory browser, as Dirble doesn't seem very reliable.
* Cleaned up IceCast Radio panel so it now follows the style of the main UI, instead of being simply a modded version of the xiph.org directory web page.
* Added [romonitor](/RompR/Mobile-Devices) so playcounts still get updated while mobile devices sleep
* Added [mopidy_scan](/RompR/Rompr-And_Mopidy#scanning-local-files) so Mopidy local files can be scanned without having to do it manually
* Local Album Art embedded in Music Files can now be accessed, thanks to an updated getid3.
* When downloading Album Art automatically, Google images will now be used if nothing can be found elsewhere. However, all Google operations now require you to supply your own [API Key](/RompR/Album-Art-Manager#using-google-images-to-find-album-art)
* Updated German Translation from Frank Schraven
* Added help links to many of the UI elements, leading directly to these docs
* Many, many other tweaks and bugfixes. Almost all the code has been looked at and tweaked.
