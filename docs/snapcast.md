# Snapcast

[Snapcast](https://github.com/badaix/snapcast) is a system for playing synchronisde audio in multiple rooms simultaneously. Rompr contains full support for contrlling a Snapcast server.

## Configuration

Enter the hostname and port for your Snapcast server in the onfiguration panel. The panel will then update to show your Snapcast network.
You should use the port for the HTTP JSON-RPC API, as defined in your snapserver.conf. By default this is enabled on port 1780.
Note that earlier versions of RompR used snapserver's TCP port but this changed in RompR version 1.51 - using the HTTP port allows multiple RompRs to update their Snapcast info simultaneously.
On the phone and tablet skins this information will appear in the volume control dropdown, underneath the Players.

![](images/snapcast1c.png)

Groups can be muted using the Mute icon next to the group and renamed by editing the name.
Clients can be muted, removed, and have their volume adjusted. To rename a client just type over it.

## Assigning Streams to Groups

If your Snapcast server has multiple streams, you can assign a stream to a group by using the dropdown 'hamburger' icon. Just click a stream to assign it to the group.
If you only have one stream, nothing will be shown here.

![](images/snapcast2c.png)

## Assigning Clients to Groups and Setting Client Latency

To move a client to a different group or set its latency, use the hamburger menu next to the Client. This will also show you some system information about that client.
Note that if you only have one group, you won't see the 'Change Group' selection.

![](images/snapcast3b.png)
