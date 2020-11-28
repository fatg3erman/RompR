# The Album Art Manager

By default RompЯ will attempt to download album art for albums in your Collection or for those that come up in a search. If it fails to find anything or you'd like to change the art for some albums you can use the Album Art Manager.

![](images/albumart.png)

This should be fairly self-explanatory.

* 'Get Missing Covers' will attempt to find covers for any albums that don't have one.
* 'Ignore Local Images' will force Rompr to download all album art fomr online sources, even if local images are available
* 'Follow Progress' will make the display auto-scroll to the cover currently being downloaded

To manually change the cover for an album you can:

* Drag an image from your hard drive or another browser window directly onto the cover in the Album Art Manager.
* Click on the image to open the image selector.

![](images/albumart2.png)

By default the image selector opens a Google Images search (but see below!). You can change the search term and click 'Search' to try something else. You can use 'File Upload' to select a local file. 'Google Search In New Tab' will open a new browser tab on Google Images with the current search term.

## Using Local Images

For local music files you may already have album art stored alongside those files or embedded in them. RompЯ can access this IF your webserver is running on the same computer as where you music files are stored.

First, go to the Configuration panel and enter the full path to your music in the text box under 'Album Art'. This MUST match the Local Folders configuration setting in your mpd.conf or mopidy.conf.

Now when RompЯ searches for album art it will use your local images in preference. If you have lots of local images for an album the image selector will allow you to choose between them.

_Note: this setting only works if you're using MPD, or Mopidy's local backend. Other mopidy backends that play local files (eg beets) will not work_

## Getting Album Images from Mopidy

Mopidy can serve Album Art for most of the backends it supports - including Local Files. You do not need to set the path to your music, nor does your webserver have to be on the same machine as your music or as mopidy.

However you need to have Mopidy's HTTP frontend enabled and working correctly as described [here](/RompR/Rompr-And-Mopidy)

### Archiving Local Images

Once you've downloaded all the art for your Collection, you can archive the images for your local files if you have set the path correctly as above. This is a manual step and requires a terminal open on the PC where your webserver runs.

    cd /PATH/TO/ROMPR
    cd utils
    php ./archiveimages.php

This will copy RompЯ's images into your Local Music folders.

### Using Google Images To Find Album Art

Direct search from within RompR is no longer supported, since Google's terms of use have become far too restrictive, their API too often changing, and their developer website too complex for anybody who still has a life to bother trying to understand.

Basically, I can't be fussed with it any more. Screw you, Google.

Google Search In New Tab still works, and you can usually drag a search result directly over the (small) album image in the Album Art Manager.