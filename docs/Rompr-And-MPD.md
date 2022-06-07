# Using RompЯ with MPD

[MPD](https://www.musicpd.org/) is the original Music Player Daemon. Fast and solid, it works with RompR without any additional configuration.
However, you can make RompR work better with it by enabling RompR's Websocket Server, which allows the browser to make a direct connection
to MPD. This makes the UI more responsive.

The Websocket Server is written in Python, because PHP isn't very good at that kind of thing. You will need some requirements:

	python3
	python3-telnetlib
	python3-asyncio
	python3-websockets

Python probably needs to be at least version 3.7. telnetlib and asyncio are normally installed as part of a base python3 install.
Websockets probably isn't installed and needs to be at least Version 10. At the time of writing you will have to install this from
pip on most current distributions as even Ubuntu 22.04 is shipping version 8.

	sudo pip3 install websockets

* Note the use of sudo above. The Websocket Server will be started by the webserver, hence the websockets package needs to be
globally available. On most systems using pip3 without sudo will install it only for the user you're logged in as.

* Note that there is also a package for python3 called 'websocket'. This is NOT the same package as websockets and will not work.

* The Websocket Server can only connect to a TCP port, using a Unix Socket is not supported.

I've tested the Websocket Server with Python 3.9 and websockets 10.3. It is still experimental so I can't guarantee it will work.



If you get a permanent message saying 'Mopidy has stopped responding' this means the backend (web server) is able to connect to the MPD interface
but your browser is not able to connect to the Websocket Server. Yes, I know it says 'Mopidy' - this is because the Websocket Server was written
to emulate functionality that Mopidy already has, and the UI uses the same code.
If you get problems try not using 'localhost' in your player definition.
