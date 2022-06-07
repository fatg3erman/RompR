#!/usr/bin/python3

import json
import re
import asyncio
import websockets
import socket
import time
import argparse

CONNECTIONS = set()
connected = False
args = None

# You MUST pass currenthost and it MUST be the first argument,
# Otherwise the PHP code will keep trying to create new instances of this.
# If you're starting this as a daemon you MUST pass the parameters in the
# order in which they're listed here, for the same reason.
# To use a UNIX socket, do not pass mpdhost or mpdport, for the same reason
# If you're not usng a UNIX socket, do not pass the parameter
# mpdpassword should only be passed if you need a password and have
# configured it in RompR's player definition

def parse_commandline_arguments():
	parser = argparse.ArgumentParser()
	parser.add_argument("--currenthost", default=None)
	parser.add_argument("--wsport", type=int, default=8001)
	parser.add_argument("--mpdhost", default="localhost")
	parser.add_argument("--mpdport", type=int, default=6600)
	parser.add_argument("--unix", default=None)
	parser.add_argument("--mpdpassword", default=None)
	return parser


def wait_for_mpd_message():
	global connected
	# Blocking IO, so run using asyncio.to_thread and await the output
	connect_to_mpd()
	output = None
	try:
		MPD.send('idle player playlist mixer'.encode('ascii') + b"\n")
		message = wait_for_mpd_response()
		print(message)
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
		MPD.close()
		connected = False

	return output


def wait_for_mpd_response():
	global connected
	line = ''
	while not 'OK' in line and not 'ACK' in line:
		b = MPD.recv(1024).decode('ascii')
		if b == '':
			# If we read nothing then the other end has gone away
			MPD.close()
			connected = False
			return line
		else:
			line += b

	return line


def connect_to_mpd():
	global connected
	global MPD
	while connected == False:
		print("Connecting to MPD")
		MPD = socket.socket(SOCKET_TYPE, socket.SOCK_STREAM)

		try:
			MPD.connect(PORT)
			junk = wait_for_mpd_response()
			print("Connected")
			connected = True
		except ConnectionRefusedError:
			print("Connection Refused")
			time.sleep(10)

		if args.mpdpassword is not None:
			MPD.send(b'password ' + args.mpdpassword.encode('ascii') + b"\n")
			junk = wait_for_mpd_response()



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
	async with websockets.serve(register_handler, "", args.wsport):
		await do_poll_stuff()


parser = parse_commandline_arguments()
args = parser.parse_args()
if args.unix is None:
	SOCKET_TYPE = socket.AF_INET
	PORT = (args.mpdhost, args.mpdport)
else:
	SOCKET_TYPE = socket.AF_UNIX
	PORT = args.unix


if __name__ == "__main__":
	asyncio.run(main())

