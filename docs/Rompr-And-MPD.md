# Using RompЯ with MPD

[MPD](https://www.musicpd.org/) is the original Music Player Daemon. Fast and solid, it works with RompR without any additional configuration.
However, you can make RompR work better with it by enabling RompR's Websocket Server, which allows the browser to make a direct connection
to MPD. This makes the UI more responsive.

The Websocket Server is written in Python, because PHP isn't very good at that kind of thing. You will need some requirements:

	python3
	python3-asyncio
	python3-websockets

Python probably needs to be at least version 3.9.

* Note that there is also a package for python3 called 'websocket'. This is NOT the same package as websockets and will not work.

I've tested the Websocket Server with Python 3.9 and Python 3.10 using websockets 10.3. It is still experimental so I can't guarantee it will work.

## To Enable the Websocket Server

Edit the [Player Definition](/RompR/Using-Multiple-Players) to set a port for the Websocket. The first time you start RompR it will have chosen a default
value for you. It needs to be different from the Socket port and all MPD players need to use a different port.


## Troubleshooting

If you enable debug logging at level 6, the actual command being used by RompR will appear in the log near the start.

To run it from the command-line to test for problems, run

	python3 /PATH/TO/ROMPR/player/mpd/mpd_websocket.py --currenthost=PLAYER --wsport=WEBSOCKET --mpdhost=HOST --mpdport=PORT

Where

* PLAYER is the name of the Player as it appears in the player definitions
* WEBSOCKET is the port number you configured above
* HOST is the IP address of your Player as configured the player definitions
* PORT is the port number as defined the Player Definitions

If you're using a UNIX socket instead of Host and Port use:

	python3 /PATH/TO/ROMPR/player/mpd/mpd_websocket.py --currenthost=PLAYER --wsport=WEBSOCKET --unix=/path/to/unix/socket

If you need a password, add a --mpdpassword= parameter to the end

You MUST specify the parameters in that order or RompR will attempt to kill the process when it starts up, and will not be able to.
