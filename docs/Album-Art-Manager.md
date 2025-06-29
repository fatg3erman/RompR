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

## Getting Album Images from MPD

If you have MPD version 0.22.4 or greater RompR will use MPD to scan your files for artwork. You do not need to set the path to your music, nor does your webserver have to be on the same machine as your music or as mpd.

## Using individual images for different tracks

This is quite niche but there is the occasional album where there are different images for each track. Under the drop down menu next to every album there is an option to 'Scan each track for an image'.
If you select this option RompR will try to use the separate image for each track. This requires either your local music path to be set as above, or for you to be using MPD 0.22.4 or higher.

### Archiving Local Images

Once you've downloaded all the art for your Collection, you can archive the images for your local files if you have set the path correctly as above. This is a manual step and requires a terminal open on the PC where your webserver runs.

    cd /PATH/TO/ROMPR
    cd utils
    php ./archiveimages.php

This will copy RompЯ's images into your Local Music folders.

### Using Brave Image Search To Find Album Art

This version of RompR now uses Brave Search to search for Album Art. To use this functionality you'll need to sign up for a Free Brave API account,
then create an API key and enter it into the setup screen you access from /rompr?setup on your RompR installtion. I could try and describe
how you do this but it changes a lot so my docs would quickly go out of date. Instead I would start here https://brave.com/search/api/
and once you have an API key you're good to go. Note you will need a credit card but the Free subscription works.

We've previously used Google (too complicated) and Bing (now replaced with a useless AI API) as search services. Hopefully Brave will now see us through for another few years.

By entering any information relating to your Brave account into RompR you are agreeing that the developers of RompR are not responsible if you end up paying anything for your Brave account.
Sorry to have to say that, but some people are dicks.