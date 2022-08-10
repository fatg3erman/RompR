# Using RompЯ with MPD

[MPD](https://www.musicpd.org/) is the original Music Player Daemon. Fast and solid, it works with RompR without any additional configuration.
However, you can make RompR work better with it by enabling RompR's Websocket Server, which allows the browser to make a direct connection
to MPD. This makes the UI more responsive.

The Websocket Server is written in Python, because PHP isn't very good at that kind of thing. You will need some requirements:

	python3
	python3-asyncio
	python3-websockets

Python probably needs to be at least version 3.9. asyncio is normally installed as part of a base python3 install.
Websockets probably isn't installed and needs to be at least Version 10. At the time of writing you will have to install this from
pip on most current distributions as even Ubuntu 22.04 is only shipping version 9.

	sudo pip3 install websockets

* Note the use of sudo above. The Websocket Server will be started by the webserver, hence the websockets package needs to be
globally available. On most systems using pip3 without sudo will install it only for the user you're logged in as.

* Note that there is also a package for python3 called 'websocket'. This is NOT the same package as websockets and will not work.

I've tested the Websocket Server with Python 3.9 and Python 3.10 using websockets 10.3. It is still experimental so I can't guarantee it will work.

## To Enable the Websocket Server

Go to /rompr/?setup and add a port number into the box provided. It just needs to be a port that isn't being used by anything else on your machine.

![](/images/mpdsocket.png)

When you refresh the browser, provided you are connecting to an MPD (not Mopidy) Player, RompR will start the websocket server.
You will see a connection notification that has two port numbers in it, for example 'Connected to MPD at localhost:6600/8001'.
(If you're using a UNIX socket for MPD you will see 'Connected to MPD at ip.address.of.mpd/8001')

![](/images/mpd_on_ws.png)

If you don't get the second port number then the websocket server failed to start.

If you get a permanent message saying 'Player has stopped responding' this means the backend (web server) is able to connect to the MPD interface
but your browser is not able to connect to the Websocket Server. If you get problems, try not using 'localhost' in your player definition and refresh
the browser.

## Troubleshooting

If you enable debug logging at level 6, the actual command being used by RompR will appear in the log near the start.

To run it from the command-line to test for problems, run

	python3 /PATH/TO/ROMPR/player/mpd/mpd_websocket.py --currenthost=PLAYER --wsport=WEBSOCKET --host=HOST --port=PORT

Where

* PLAYER is the name of the Player as it appears in the player definitions
* WEBSOCKET is the port number you configured above
* HOST is the IP address of your Player as configured the player definitions
* PORT is the port number as defined the Player Definitions

If you're using a UNIX socket instead of Host and Port use:

	python3 /PATH/TO/ROMPR/player/mpd/mpd_websocket.py --currenthost=PLAYER --wsport=WEBSOCKET --unix=/path/to/unix/socket

If you need a password, add a --mpdpassword= parameter to the end

You MUST specify the parameters in that order or RompR will attempt to kill the process when it starts up, and will not be able to.

