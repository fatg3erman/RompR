# Automatic Collection Updates

You can now use cron (the Linux system scheduler) to perform automatic updates of RompЯ's collection.

If you're using mopidy you will need to ensure the cron job also runs 'mopidy local scan' and note that this must run as the user that mopidy runs as.

If you're using mpd, mpd has an option in its config file to auto_update mpd when new files are added so enable that first.

To update RompЯ's collection just create a cron job that runs

`curl -b "skin=desktop;currenthost=Default;player_backend=mpd" http://address.of.rompr/api/collection/?rebuild=yes > /dev/null`

where currenthost is the name of one of the Players defined in the Configuration menu
and player_backend MUST be mpd or mopidy, depending on what your player is.

This job can be run as any user. You will need to have curl installed.
