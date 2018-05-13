# The Album Art Manager
By default RompЯ will attempt to download album art for albums in your Collection or for those that come up in a search. If it fails to find anything or you'd like to change the art for some albums you can use the Album Art Manager.

![](images/albumart.png)

This should be fairly self-explanatory. 'Get Missing Covers' will attempt to find covers for any albums that don't have one. 'Find Small Images' will attempt to filter out any images which are very small (because they look bad in the main window) and replace them with bigger versions.

To manually change the cover for an album you can:

* Drag an image from your hard drive or another browser window directly onto the cover in the Album Art Manager.
* Click on the image to open the image selector.

By default the image selector opens a Google Images search. You can change the search term and click 'Search' to try something else. You can use 'File Upload' to select a local file. 'Google Search In New Tab' will open a new browser tab on Google Images with the current search term.

## Using Local Images
For local files you may already have album art stored alongside those files. RompЯ can access this IF your webserver and your files are stored on the same computer.

First, go to the configuration panel and enter the full path to your music in the text box under 'Album Art'. This MUST match the Local Folders configuration setting in your mpd.conf or mopidy.conf.

Now when RompЯ searches for album art it will use your local images in preference.

### Archving Local Images
Once you've downloaded all the art for your Collection, you can archive the images for your Local files if you have set the path correctly as above. This is a manual step and requires a terminal open on the PC where your webserver runs.

    cd /PATH/TO/ROMPR
    cd utils
    php ./archiveimages.php
    
This will copy RompЯ's images into your Local Music folders.
