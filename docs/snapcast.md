# Snapcast

[Snapcast](https://github.com/badaix/snapcast) is a system for playing synchronised audio in multiple rooms simultaneously. Rompr contains full support for contrlling a Snapcast server.

## Configuration

Enter the hostname and port for your Snapcast server in the onfiguration panel. The panel will then update to show your Snapcast network.
You should use the port for the HTTP JSON-RPC API, as defined in your snapserver.conf. By default this is enabled on port 1780.
Note that earlier versions of RompR used snapserver's TCP port but this changed in RompR version 1.51 - using the HTTP port allows multiple
RompRs to update their Snapcast info simultaneously.

![](images/snapcast1d.png)

The Snapcast panel shows you groups and the Clients in those groups.

Groups can be muted using the Mute icon next to the group and renamed by editing the name.

The volume controls for all the clients in a group can be locked together by clicking the padlock icon. When locked together,
adjusting the volume of one client will adjust the volume of all the others by the same amount. You cannot adjust the volume of any client
to a point where it would make another client's volume go over 100% or below 0%.

Clients can be muted, removed, renamed, and have their volume adjusted.

If your Snapcast server has multiple streams, you can assign a stream to a group by using the menu to the right of the group name

On the phone and tablet skins the controls will appear in the volume control dropdown, underneath the Players.

There is also an option to replace RompR's main volume control with the Snapcast volume control. On the phone skin this basically just hides the main volume control
and Mute button and leaves everything else where it was. On the Desktop skin this replaces the volume control in the main UI with multiple sliders, one for each Snapcast client.

![](images/snapcast1e.png)

## Assigning Clients to Groups and Setting Client Latency

To move a client to a different group or set its latency, use the hamburger menu next to the Client.
Select a group from the list.

![](images/snapcast3c.png)

![](images/snapcast4c.png)
