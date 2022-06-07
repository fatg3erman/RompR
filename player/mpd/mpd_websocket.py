#!/usr/bin/python3

import json
import re
import asyncio
import websockets
import telnetlib
import time
import argparse

CONNECTIONS = set()
MPD = telnetlib.Telnet()
args = None

# You MUST pass currenthost and it MUST be the first argument,
# Otherwise the PHP code will keep trying to create new instances of this.
# If you're starting this as a daemon you MUST pass the parameters in the
# order in which they're listed here, for the same reason.
# mpdpassword should only be passed if you need a password and have
# configured it in RompR's player definition

def parse_commandline_arguments():
	parser = argparse.ArgumentParser()
	parser.add_argument("--currenthost", default=None)
	parser.add_argument("--mpdhost", default="localhost")
	parser.add_argument("--mpdport", default=6600)
	parser.add_argument("--wsport", default=8001)
	parser.add_argument("--mpdpassword", default=None)
	return parser


def wait_for_mpd_message():
	# Blocking IO, so run using asyncio.to_thread and await the output
	output = None
	try:
		MPD.write('idle player playlist mixer'.encode('ascii') + b"\n")
		message = MPD.read_until(b"OK\n").decode('ascii')
		lines = message.split("\n")
		output = {}
		for line in lines:
			if 'changed' in line:
				parts = line.split(':')
				event_type = parts[1].strip(" \n")
				# For compatability with the Mopidy code
				if event_type == "playlist":
					event_type = "tracklist_changed"

				output['event'] = event_type
	except (EOFError, OSError, BrokenPipeError):
		connect_to_mpd()

	return output


def connect_to_mpd():
	while True:
		try:
			MPD.open(args.mpdhost, args.mpdport)
			pattern = re.compile("OK MPD .*")
			MPD.expect([b"OK MPD .*"])
			if args.mpdpassword is not None:
				MPD.write(b'password ' + args.mpdpassword.encode('ascii') + b"\n")
				MPD.read_until(b"OK\n")

			return True
		except ConnectionRefusedError:
			time.sleep(10)


async def register_handler(websocket, path):
	CONNECTIONS.add(websocket)
	try:
		await websocket.wait_closed()
	finally:
		CONNECTIONS.remove(websocket)


async def do_poll_stuff():
	while True:
		message = await asyncio.to_thread(wait_for_mpd_message)
		if message is not None and message is not {}:
			websockets.broadcast(CONNECTIONS, json.dumps(message))


async def main():
	connect_to_mpd()
	async with websockets.serve(register_handler, "", args.wsport):
		await do_poll_stuff()


parser = parse_commandline_arguments()
args = parser.parse_args()

if __name__ == "__main__":
	asyncio.run(main())

