var snapsocket = function() {

	var socket = null;
	var connected = false;
	var reconnect_timer;

	function socket_closed() {
		debug.warn('SNAPSOCKET', 'Socket was closed');
		if (connected) {
			socket_error();
		}
		socket = null;
	}

	function socket_message(message) {
		if (message.data) {
			var json = JSON.parse(message.data);
			debug.log('SNAPSOCKET', 'Got', json);
			if (json.id && json.result && json.result.server) {
				snapcast.gotStatus(json);
			} else if (json.method) {
				switch (json.method) {
					case 'Server.OnUpdate':
						json.result = json.params;
						snapcast.gotStatus(json);
						break;

					case 'Group.OnMute':
					case 'GrClient.OnNameChanged':
						debug.log('PARP', json);
						snapcast.updateGroup(json.params);
						break;

					case 'Client.OnVolumeChanged':
					case 'Client.OnLatencyChanged':
					case 'Client.OnNameChanged':
						snapcast.updateClient(json.params);
						break;

					default:
						snapcast.updateStatus();
						break;
				}
			} else {
				snapcast.updateStatus();
			}
		}
	}

	function socket_error() {
		snapcast.gotStatus({error: {data: language.gettext('snapcast_notthere')}});
		connected = false;
		snapsocket.close();
		clearTimeout(reconnect_timer);
		reconnect_timer = setTimeout(snapcast.updateStatus, 10000);
	}

	function socket_open() {
		debug.mark('SNAPSOCKET', 'Socket is open');
		clearTimeout(reconnect_timer);
		connected = true;
	}

	return {
		initialise: async function() {
			if (!connected || !socket || socket.readyState > WebSocket.OPEN) {
				debug.mark('SNAPSOCKET', 'Connecting Socket');
				socket = new WebSocket('ws://'+prefs.snapcast_server+':'+prefs.snapcast_http+'/jsonrpc');
				socket.onopen = socket_open;
				socket.onerror = socket_error;
				socket.onclose = socket_closed;
				socket.onmessage = socket_message;
			}
			while (socket && socket.readyState == WebSocket.CONNECTING) {
				await new Promise(t => setTimeout(t, 100));
			}
			if (!socket || socket.readyState > WebSocket.OPEN) {
				socket_error();
			}
			return connected;
		},

		close: function() {
			if (connected || socket) {
				socket.close();
			}
		},

		send: async function(data, callback) {
			if (await snapsocket.initialise()) {
				socket.send(JSON.stringify(data));
			}
		}
	}

}();

var snapcast = function() {

	var groups = {};
	var streams = new Array();
	var id = 0;
	var lastid = 0;
	var ew = null;

	function snapcastRequest(parms, callback) {
		id++;
		parms.id = id;
		parms.jsonrpc = "2.0";
		snapsocket.send(parms);
	}

	function make_error_window(data) {
		if (ew === null) {
			ew = $('<div>', {class: "fullwidth textcentre", style: "padding:4px"}).insertBefore('#snapcastgroups');
			$('#snapcastgroups').addClass('canbefaded').css({opacity: '0.4'});
		}
		ew.html('<b>'+data.error.data+'</b>');
	}

	function remove_error_window() {
		if (ew !== null) {
			ew.remove();
			ew = null;
			$('#snapcastgroups').removeClass('canbefaded').css({opacity: ''});
		}
	}

	return {

		initialise: function() {
			sleepHelper.addWakeHelper(snapcast.updateStatus);
			snapcast.updateStatus();
		},

		updateStatus: function() {
			if (prefs.snapcast_server != '' && prefs.snapcast_http != '') {
				snapcastRequest({
					method: "Server.GetStatus"
				}, snapcast.gotStatus);
				$('#snapheader').show();
			} else {
				$('#snapheader').hide();
			}
		},

		gotStatus: function(data) {
			debug.debug("SNAPCAST", "Server Status", data);
			if (data.hasOwnProperty('error')) {
				make_error_window(data);
			} else if (
				(data.hasOwnProperty('id') && parseInt(data.id) == id) ||
				data.hasOwnProperty('method')
			) {
				lastid = id;
				remove_error_window();
				streams = data.result.server.streams;
				$.each(groups, function() {
					this.fresh = false;
				});
				data.result.server.groups.forEach(function(g) {
					if (groups.hasOwnProperty(g.id)) {
						groups[g.id].fresh = true;
						debug.debug('SNAPCAST','Updating group',g.id);
						groups[g.id].group.update(g);
					} else {
						debug.log('SNAPCAST','Creating new group',g.id);
						groups[g.id] = {
							fresh: true,
							group: new snapcastGroup()
						}
						groups[g.id].group.initialise();
						groups[g.id].group.update(g);
					}
				});
				$.each(groups, function(i, v) {
					if (groups[i].fresh) {
						groups[i].group.updateGroupLists();
					} else {
						groups[i].group.removeSelf();
						delete groups[i];
					}
				});
			} else {
				debug.warn('SNAPCAST','Got response with id',data.id,'expecting',id);
			}
		},

		streamInfo: function(streamid, showformat) {
			if (streams.length == 1) {
				return '';
			}
			for (var i in streams) {
				if (streams[i].id == streamid) {
					var h = streams[i].meta.STREAM;
					h += ' ('+streams[i].status.capitalize()+')';
					return h;
				}
			}
			return 'Unknown Stream';
		},

		getAllStreams: function() {
			return streams;
		},

		getAllGroups: function() {
			var retval = new Array();
			$.each(groups, function() {
				retval.push(this.group.getInfo());
			});
			return retval;
		},

		setClientVolume: function(id, volume, muted) {
			snapcastRequest({
				method: "Client.SetVolume",
				params: {
					id: id,
					volume: {
						muted: muted,
						percent: volume
					}
				}
			}, null);
		},

		setGroupMute: function(id, muted) {
			snapcastRequest({
				method: "Group.SetMute",
				params: {
					id: id,
					mute: muted
				}
			}, null);
		},

		deleteClient: function(id) {
			snapcastRequest({
				method: "Server.DeleteClient",
				params: {
					id: id
				}
			}, null);
		},

		setClientName: function(id, name) {
			debug.log('Chaning name of client',id,'to',name);
			snapcastRequest({
				method: "Client.SetName",
				params: {
					id: id,
					name: name
				}
			}, null);
		},

		setGroupName: function(id, name) {
			snapcastRequest({
				method: "Group.SetName",
				params: {
					id: id,
					name: name
				}
			}, null);
		},

		setClientLatency: function(id, latency) {
			snapcastRequest({
				method: "Client.SetLatency",
				params: {
					id: id,
					latency: parseInt(latency)
				}
			}, null);
		},

		setStream: function(group, stream) {
			snapcastRequest({
				method: "Group.SetStream",
				params: {
					id: group,
					stream_id: stream
				}
			}, null);
		},

		addClientToGroup: function(client, group) {
			var clients = groups[group].group.getClients();
			clients.push(client);
			snapcastRequest({
				method: "Group.SetClients",
				params: {
					clients: clients,
					id: group
				}
			}, null);
		},

		clearEverything: function() {
			streams = [];
			groups = {};
			$('#snapcastgroups').empty();
			snapsocket.close();
		},

		updateClient: function(params) {
			$.each(groups, function() {
				found = this.group.updateClient(params);
				return !found;
			});
		},

		updateGroup(params) {
			groups[params.id].group.setParams(params);
		}

	}

}();

function snapcastGroup() {

	var self = this;
	var clients = {};
	var holder;
	var id = '';
	var muted;
	var streammenu;
	var name;
	var mutebutton;
	var changeNameTimer;

	function setName(data) {
		if (data.name) {
			name = data.name;
		} else {
			name = id;
		}
		holder.find('input[name="groupname"]').val(name);
	}

	function setMuted(data) {
		muted = data.muted;
		var icon = data.muted ? 'icon-output-mute' : 'icon-output';
		holder.find('i[name="groupmuted"]').removeClass('icon-output icon-output-mute').addClass(icon);
	}

	this.updateClient = function(params) {
		if (clients.hasOwnProperty(params.id)) {
			clients[params.id].client.setParams(params);
			return true;
		}
		return false;
	}

	this.updateGroupLists = function() {
		$.each(clients, function() {
			this.client.updateGroupList();
		});
	}

	this.initialise = function() {
		holder = $('<div>', {class: 'snapcastgroup fullwidth'}).appendTo('#snapcastgroups');
		var title = $('<div>', {class: 'containerbox snapgrouptitle dropdown-container'}).appendTo(holder);
		var n = $('<input>', {type: "text", class: "expand tag snapclientname", name: "groupname"}).appendTo(title);
		n.on('keyup', self.keyUp)

		var sel = $('<div>', {class: 'selectholder boycie expand', style: 'margin-right: 1em'}).appendTo(title);
		streammenu = $('<select>', {class: 'snapgroupstream'}).appendTo(sel);

		mutebutton = $('<i>', {class: "podicon fixed clickicon", name: "groupmuted"}).appendTo(title).on('click', self.setMute);
	}

	this.removeSelf = function() {
		holder.remove();
	}

	this.getId = function() {
		return id;
	}

	this.keyUp = function() {
		clearTimeout(changeNameTimer);
		changeNameTimer = setTimeout(self.changeName, 2500);
	}

	this.changeName = function() {
		snapcast.setGroupName(id, holder.find('input[name="groupname"]').val());
	}

	this.update = function(data) {
		id = data.id;
		setName(data);
		setMuted(data);

		$.each(clients, function() {
			this.fresh = false;
		});
		data.clients.forEach(function(c) {
			if (clients.hasOwnProperty(c.id)) {
				clients[c.id].fresh = true;
				debug.debug('SNAPCAST','Updating clients',c.id);
				clients[c.id].client.update(id, c);
			} else {
				debug.log('SNAPCAST','Creating new client',c.id);
				clients[c.id] = {
					fresh: true,
					client: new snapcastClient()
				}
				clients[c.id].client.initialise(holder);
				clients[c.id].client.update(id, c);
			}
		});
		$.each(clients, function(i, v) {
			if (!clients[i].fresh) {
				clients[i].client.removeSelf();
				delete clients[i];
			}
		});

		streammenu.off('change');
		streammenu.html('');
		var s = snapcast.getAllStreams();
		for (var i in s) {
			$('<option>', {value: s[i].id}).html(snapcast.streamInfo(s[i].id, true)).appendTo(streammenu);
		}
		streammenu.val(data.stream_id);
		streammenu.on('change', self.changeStream);

	}

	this.setMute = function() {
		snapcast.setGroupMute(id, !muted);
	}

	this.changeStream = function(e) {
		var streamid = $(this).val();
		debug.log('SNAPCAST', 'Group',id,'changing stream to',streamid);
		snapcast.setStream(id, streamid);
	}

	this.getInfo = function() {
		return {id: id, name: name};
	}

	this.getClients = function() {
		var retval = new Array();
		$.each(clients, function(i,v) {
			retval.push(clients[i].client.getId());
		});
		return retval;
	}

	this.setParams = function(params) {
		if (params.hasOwnProperty('mute')) {
			params.muted = params.mute;
			setMuted(params);
		}
		if (params.hasOwnProperty('name')) {
			setName(params);
		}
	}
}

function snapcastClient() {

	var self = this;
	var holder;
	var id = '';
	var volume;
	var volumepc;
	var muted;
	var vc;
	var groupmenu;
	var grouplist;
	var groupid;
	var connected;
	var changeNameTimer;
	var changeLatencyTimer;

	function updateVolume(params) {
		muted = params.muted;
		volumepc = params.percent;
		volume.volumeControl("displayVolume", params.percent);
		var icon = params.muted ? 'icon-output-mute' : 'icon-output';
		holder.find('i[name="clientmuted"]').removeClass('icon-output icon-output-mute').addClass(icon);
	}

	function updateName(g) {
		holder.find('input[name="clientname"]').val(g);
	}

	function updateLatency(l) {
		holder.find('input[name="latency"]').val(l);
	}

	this.initialise = function(parentdiv) {
		holder = $('<div>', {class: 'snapcastclient'}).appendTo(parentdiv);
		var title = $('<div>', {class: 'containerbox dropdown-container'}).appendTo(holder);
		var n = $('<input>', {type: "text", class: "expand tag snapclientname", name: "clientname"}).appendTo(title);
		n.on('keyup', self.keyUp);

		$('<div>', {class: 'fixed tag', name: 'notcon'}).appendTo(title);
		var client = $('<div>', {class: 'containerbox'}).appendTo(holder);
		var m = $('<i>', {class: "podicon fixed icon-menu clickicon"}).appendTo(title).on('click', self.setGroup);
		var rb = $('<i>', {class: "fixed podicon icon-cancel-circled clickicon"}).appendTo(title).on('click', self.deleteClient);
		vc = $('<div>', {class: 'containerbox dropdown-container invisible'}).appendTo(holder);
		volume = $('<div>', {class: 'expand playlistrow2'}).appendTo(vc);
		var m = $('<i>', {class: "podicon fixed clickicon", name :"clientmuted"}).appendTo(vc).on('click', self.setMute);
		volume.volumeControl({
			orientation: 'horizontal',
			command: self.setVolume
		});

		groupmenu = $('<div>', {class: 'toggledown invisible'}).insertAfter(title);
		var j = $('<div>', {class: "containerbox wrap dropdown-container"}).appendTo(groupmenu);

		var wrapper = $('<div>', {class: 'expand', style: 'margin-right: 1em'}).appendTo(j);
		var sel = $('<div>', {class: 'selectholder boycie'}).appendTo(wrapper);
		grouplist = $('<select>', {class: 'snapclientgroup'}).appendTo(sel);

		var lholder = $('<div>', {class: 'containerbox fixed dropdown-container'}).appendTo(j);
		$('<div>', {class: 'fixed padright'}).appendTo(lholder).html(language.gettext('snapcast_latency'));
		lb = $('<input>', {type: 'text', class: 'fixed', name: "latency", style: "width:4em"}).appendTo(lholder);
		lb.on('keyup', self.setLatency);
	}

	this.removeSelf = function() {
		holder.remove();
	}

	this.getId = function() {
		return id;
	}

	this.keyUp = function() {
		clearTimeout(changeNameTimer);
		changeNameTimer = setTimeout(self.changeName, 2500);
	}

	this.setLatency = function() {
		clearTimeout(changeLatencyTimer);
		changeLatencyTimer = setTimeout(self.changeLatency, 500);
	}

	this.update = function(index, data) {
		groupid = index;
		id = data.id;
		connected = data.connected;
		var g = '';
		if (data.config.name) {
			g = data.config.name;
		} else {
			g = data.host.name+' - '+data.host.os;
		}
		updateName(g);
		if (data.connected) {
			holder.find('div[name="notcon"]').html('');
			updateVolume(data.config.volume);
			updateLatency(data.config.latency);
			vc.removeClass('invisible');
		} else {
			holder.find('div[name="notcon"]').html(language.gettext('snapcast_notconnected'));
			if (!vc.hasClass('invisible')) {
				vc.addClass('invisible');
			}
		}

	}

	this.updateGroupList = function() {
		grouplist.off('change');
		grouplist.html('');
		var g = snapcast.getAllGroups();
		for (var i in g) {
			$('<option>', {value: g[i].id}).html(g[i].name).appendTo(grouplist);
		}
		grouplist.val(groupid);
		grouplist.on('change', self.changeGroup);
	}

	this.setParams = function(params) {
		if (params.volume) {
			updateVolume(params.volume);
		}
		if (params.name) {
			updateName(params.name);
		}
		if (params.latency || params.latency === 0) {
			updateLatency(params.latency);
		}
	}

	this.setVolume = function(v) {
		v = Math.round(v);
		debug.log('SNAPCAST','Client',id,'setting volume to',v);
		snapcast.setClientVolume(id, v, muted);
	}

	this.setMute = function() {
		debug.log('SNAPCAST','Client',id,'toggling mute state');
		snapcast.setClientVolume(id, volumepc, !muted);
	}

	this.deleteClient = function() {
		snapcast.deleteClient(id);
	}

	this.changeName = function() {
		snapcast.setClientName(id, holder.find('input[name="clientname"]').val());
	}

	this.changeLatency = function() {
		snapcast.setClientLatency(id, holder.find('input[name="latency"]').val());
	}

	this.setGroup = function() {
		groupmenu.slideToggle('fast');
	}

	this.changeGroup = function(e) {
		var groupid = $(this).val();
		debug.log('SNAPCAST', 'Client',id,'changing group to',groupid);
		snapcast.addClientToGroup(id, groupid);
	}

}