# Automation and Smart Home

Because Rompr is a server, it is possible to use it as a REST API for MPD. You could use this in conjunction with Smart Home devices that permit you to write custom code, to automate the music in your home, or for any other purpose.

You can find an example project that a GitHub user has created for smartthings [here](https://github.com/nivw/smartthings_to_rompr)

The following will work with both MPD and Mopidy.

## Communication

Most controls you will want to use are accessed via a simple HTTP POST request to the URL

    http://address.of.rompr/api/player/

note the trailing '/', do not omit this!

## Command Set

Rompr uses a superset of the [Music Player Daemon Command Set](https://www.musicpd.org/doc/protocol/command_reference.html)

The body of your POST request must contain a properly formatted JSON array of MPD commands. Each command itself is an array, with the first value being the command and any subsequent values being the parameters for that command, if required.

So for example, to start playback, your JSON string would look like

    [["play"]]

to clear the play queue, add a track and start playback, you could use

    [["clear"], ["add", "uri/of/some/track.mp3"], ["play"]]

## Responses

The response you get back from the POST request will be a JSON string which combines the output of MPD's ['status' and 'currentsong' commands](https://www.musicpd.org/doc/protocol/command_reference.html#status_commands). Here's an example (from Mopidy), once the JSON has been decoded.

    Album: "Living With a Tiger"​
    AlbumArtist: "Acoustic Ladyland"
    Artist: "Acoustic Ladyland"
    Date: "2009-07-06"
    Disc: "1"
    Genre: "Jazz"
    Id: "2"
    "Last-Modified": "2013-08-17T19:16:37Z"
    ​MUSICBRAINZ_ALBUMARTISTID: "187d33be-74c2-48bd-bf44-412c06b94eee"
    ​MUSICBRAINZ_ALBUMID: "64dbb31b-c549-4a20-b1d1-7f69503ef51c"
    ​MUSICBRAINZ_ARTISTID: "187d33be-74c2-48bd-bf44-412c06b94eee"
    ​MUSICBRAINZ_TRACKID: "00397625-ea7d-4870-ad3e-ac32ee2739e5"
    ​Pos: "0"
    ​Time: "150"
    ​Title: "Sport Mode"
    ​Track: "1/10"
    ​"X-AlbumUri": "local:album:md5:f13a06a18ababeb04d4047c9307c0bb2"
    ​bitrate: "0"
    ​consume: "0"
    ​elapsed: "4.430"
    ​file: "local:track:Acoustic%20Ladyland/Living%20With%20a%20Tiger/01%20Sport%20Mode.mp3"
    ​nextsong: "1"
    ​nextsongid: "3"
    ​playlist: "12"
    ​playlistlength: "10"
    ​random: "0"
    ​repeat: "0"
    ​single: "0"
    ​song: "0"
    ​songid: "2"
    ​state: "play"
    ​time: "4:150"
    ​volume: "100"
    ​xfade: "0"

An additional 'error' field will be present, containing MPD's error output, if an error occurred.

## Additional Commands

Rompr's command set is a superset of mpd's. The one extra command you may want to use is

    [["additem", "aalbum1234"]]

Which will add an entire album to your Play Queue in one go. The number '1234' is Rompr's Albumindex, which you will have to find by examining the Rompr database and looking at the table Albumtable. Note the extra 'a' in front of 'album'.

You can also do

    [["additem", "aartist1234"]]

to add all tracks by an artist. The number 1234 is Rompr's Artistindex, which you can find from the Artisttable in the database. Note that this refers to Album Artists, not Track Artists.

## Defining Which Player To Use

You need to set Cookies on your request.

    currenthost=Name_Of_Player
    player_backend=[mpd or mopidy]

You MUST set both cookies. player_backend MUST be either 'mpd' or 'mopidy'

## Simple Command-Line Example Using Curl

Curl is a very powerful command-line tool for sending HTTP requests. Here is how to make Rompr start playback using curl:

     curl  -b "currenthost=Mopidy;player_backend=mopidy" -d '[["play"]]' -H "Content-Type: application/json" -X POST http://www.myrompr.net/api/player/

The options are:

* -b : The Cookies required to be set
* -d : The JSON data
* -H : a Content-Type header specifying JSON. This is required for curl
* -X : The type of request (POST)

The response you get back looks like

````
{"volume":"100","repeat":"0","random":"0","single":"0","consume":"0","playlist":"12","playlistlength":"10","xfade":"0","state":"play","song":"0","songid":"2","nextsong":"1","nextsongid":"3","time":"22:150","elapsed":"22.610","bitrate":"0","file":"local:track:Acoustic%20Ladyland\/Living%20With%20a%20Tiger\/01%20Sport%20Mode.mp3","Time":"150","Artist":"Acoustic Ladyland","Album":"Living With a Tiger","Title":"Sport Mode","Date":"2009-07-06","Track":"1\/10","Pos":"0","Id":"2","MUSICBRAINZ_ALBUMID":"64dbb31b-c549-4a20-b1d1-7f69503ef51c","AlbumArtist":"Acoustic Ladyland","MUSICBRAINZ_ALBUMARTISTID":"187d33be-74c2-48bd-bf44-412c06b94eee","MUSICBRAINZ_ARTISTID":"187d33be-74c2-48bd-bf44-412c06b94eee","Genre":"Jazz","Disc":"1","Last-Modified":"2013-08-17T19:16:37Z","MUSICBRAINZ_TRACKID":"00397625-ea7d-4870-ad3e-ac32ee2739e5","X-AlbumUri":"local:album:md5:f13a06a18ababeb04d4047c9307c0bb2"}
````

You can also specify a file that contains the JSON data. Eg create a file called 'play.json':

    [
        ["play"]
    ]

Then do

     curl -b "currenthost=Mopidy;player_backend=mopidy" -d "@play.json" -H "Content-Type: application/json" -X POST http://www.myrompr.net/api/player/

In this way you can create predefined lists of commands.

