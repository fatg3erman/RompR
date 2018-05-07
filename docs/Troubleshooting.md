# Troubleshooting

## Cannot Connect to MPD or Mopidy

### MPD on a remote PC
If you are running mpd on a different computer from your web server then you might need to change the bind_to_address in your mpd.conf as the defaults sometimes don't work. "localhost" will only accept connections from the local PC, and "any" seems to fail sometimes because it tries to bind to IPV6 first. Try:

`bind_to_address    "127.0.0.1"`

`bind_to_address    "ip.address.of.this.computer"`

## Music Collection Fails To Build

### MPD and Large Music Collections
If your music collection is quite large you may need to tweak a couple of settings in mpd.
Edit /etc/mpd.conf (or wherever your mpd conf file is) and find the line (or add the line) for max_output_buffer_size and connection_timeout

`connection_timeout        "800"`

`max_output_buffer_size    "32768"`

### Very Large Collections

You may fall foul of web server timeouts when trying to build very large music collections. You can hopefully fix this.

Firstly, your php.ini needs to have a setting for max_execution_time, as above. This is in seconds, so set it to something massive. (If you're using apache you can set this as a php_admin_value in your apache config file for RompЯ, near where all the other are).

Secondly you need to allow the server to wait a long time for output. With nginx, this is the fastcgi_read_timeout parameter that is in the example configuration above. For Apache you need to change the Timeout directive in your apache config file. Note this has to be globally for whole server, which is another good reason to use nginx :)

# Advanced Configuration

## MPD/Mopidy Addresses and Ports
In the case where your mpd server is not running on the same PC as your apache server, or you need a password for mpd, or you'd like to use a unix-domain socket to communicate with mpd, point your browser at:

http://www.myrompr.net?setup

and enter the appropriate values. This page will appear automatically if RompЯ can't communicate with mpd when you load the page.

## Proxy Configuration
You can configure RompЯ to use a web proxy from the setup page, too.


## Pages On This website

[Home](https://fatg3erman.github.io/RompR/)

[Recommended Linux Installation - with Nginx](https://fatg3erman.github.io/RompR/Recommended-Installation-on-Linux)

[Alternative Linux Installation - with Apache](https://fatg3erman.github.io/RompR/Installation-on-Linux-Alternative-Method)

[Troubleshooting](https://fatg3erman.github.io/RompR/Troubleshooting)
