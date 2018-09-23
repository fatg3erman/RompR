# How To Install RompЯ on Android

I've not tried this myself but there are reports that Rompr will run on an Android device if you install a Linux distro (eg Ubutnu) using [Linux Deploy](https://github.com/meefik/linuxdeploy) and then follow the standard [Linux install instructions](/RompR/Recommended-Installation-on-Linux).

You need to make one slight tweak to the setup:

    sudo addgroup www-data aid_inet

You should note that Rompr requires a fair amount of storage space for its cache, so you should install or symlink it as appropriate to wherever on your device has the most available storage.

