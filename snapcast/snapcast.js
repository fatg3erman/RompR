var snapsocket = function() {

	var socket = null;
	var connected = false;
	var reconnect_timer;

	function socket_closed() {
		debug.warn('SNAPSOCKET', 'Socket was closed');
		if (socket || connected) {
			socket_error();
		}
		socket = null;
		connected = false;
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
		socket.close();
		connected = false;
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
		},

		close: function() {
			if (connected || socket) {
				socket.close();
			}
		},

		send: async function(data, callback) {
			await snapsocket.initialise();
			if (socket && socket.readyState == WebSocket.OPEN) {
				socket.send(JSON.stringify(data));
			} else {
				socket_error();
			}
		}
	}

}();

var snapcast = function() {

	var groups = new Array();
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

	function findGroup(arr, id) {
		for (var i in arr) {
			if (arr[i].getId() == id) {
				return i;
			}
		}
		return false;
	}

	return {

		initialise: function() {
			sleepHelper.addWakeHelper(snapcast.updateStatus);
			snapcast.updateStatus();
		},

		updateStatus: function() {
			if (prefs.snapcast_server != '' && prefs.snapcast_port != '') {
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
			if (data.hasOwnProperty('error') && lastid != id) {
				if (ew === null) {
					ew = $('<div>', {class: "fullwidth textcentre", style: "padding:4px"}).insertBefore('#snapcastgroups');
					$('#snapcastgroups').addClass('canbefaded').css({opacity: '0.4'});
				}
				ew.html('<b>'+data.error.data+'</b>');
			} else if (
				(data.hasOwnProperty('id') && parseInt(data.id) == id) ||
				data.hasOwnProperty('method')
			) {
				lastid = id;
				if (ew !== null) {
					ew.remove();
					ew = null;
					$('#snapcastgroups').removeClass('canbefaded').css({opacity: ''});
				}
				streams = data.result.server.streams;
				var newgroups = new Array();
				for (var i in data.result.server.groups) {
					var group_exists = findGroup(groups, data.result.server.groups[i].id);
					if (group_exists === false) {
						debug.log('SNAPCAST','Creating new group',data.result.server.groups[i].id);
						var g = new snapcastGroup();
						newgroups.push(g);
						g.initialise();
						g.update(i, data.result.server.groups[i]);
					} else {
						debug.debug('SNAPCAST','Updating group',data.result.server.groups[i].id);
						newgroups.push(groups[group_exists]);
						groups[group_exists].update(i, data.result.server.groups[i]);
					}
				}
				// Find removed groups
				for (var i in groups) {
					if (findGroup(newgroups, groups[i].getId()) === false) {
						debug.log('SNAPCAST','Group',groups[i].getId(),'has been removed');
						groups[i].removeSelf();
						groups[i] = null;
					}
				}
				groups = newgroups;
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
					// if (showformat) {
					// 	h += ' <span class="snapclienthost">('+streams[i].uri.query.codec+' '+streams[i].uri.query.sampleformat+')</span>';
					// }
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
			for (var i in groups) {
				retval.push(groups[i].getInfo());
			}
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
			var groupindex = findGroup(groups, group);
			var clients = groups[groupindex].getClients();
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
			groups = [];
			$('#snapcastgroups').empty();
			snapsocket.close();
		},

		updateClient: function(params) {
			var i = 0;
			var found = false;
			while (found === false && i < groups.length) {
				found = groups[i].updateClient(params);
				i++;
			}
		}

	}

}();

function snapcastGroup() {

	var self = this;
	var clients = new Array();
	var holder;
	var id = '';
	var muted;
	var streammenu;
	var ind;
	var name;
	var mutebutton;
	var changeNameTimer;

	function findClient(arr, id) {
		for (var i in arr) {
			if (arr[i].getId() == id) {
				return i;
			}
		}
		return false;
	}

	this.updateClient = function(params) {
		var c = findClient(clients, params.id);
		if (c !== false) {
			clients[c].setParams(params);
		}
		return c;
	}

	this.initialise = function() {
		holder = $('<div>', {class: 'snapcastgroup fullwidth'}).appendTo('#snapcastgroups');
		var title = $('<div>', {class: 'containerbox snapgrouptitle dropdown-container'}).appendTo(holder);
		$('<input>', {type: "text", class: "expand tag snapclientname", name: "groupname"}).appendTo(title);

		var sel = $('<div>', {class: 'selectholder boycie expand', style: 'margin-right: 1em'}).appendTo(title);
		streammenu = $('<select>', {class: 'snapgroupstream'}).appendTo(sel);

		mutebutton = $('<i>', {class: "podicon fixed clickicon", name: "groupmuted"}).appendTo(title).on('click', self.setMute);
		mutebutton.on('keyup', self.keyUp)
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

	this.update = function(index, data) {
		ind = index;
		id = data.id;
		muted = data.muted;
		name = data.name;
		if (data.name) {
			var g = data.name;
		} else {
			var g = language.gettext('snapcast_group')+index;
		}
		holder.find('input[name="groupname"]').val(g);
		var icon = data.muted ? 'icon-output-mute' : 'icon-output';
		holder.find('i[name="groupmuted"]').removeClass('icon-output icon-output-mute').addClass(icon);

		var newclients = new Array();
		for (var i in data.clients) {
			var client_exists = findClient(clients, data.clients[i].id);
			if (client_exists === false) {
				debug.log('SNAPCAST','Creating new client',data.clients[i].id);
				var g = new snapcastClient();
				newclients.push(g);
				g.initialise(holder);
				g.update(id, data.clients[i]);
			} else {
				debug.debug('SNAPCAST','Updating client',data.clients[i].id);
				newclients.push(clients[client_exists]);
				clients[client_exists].update(id, data.clients[i]);
			}
		}
		// Find removed clients
		for (var i in clients) {
			if (findClient(newclients, clients[i].getId()) === false) {
				debug.log('SNAPCAST','Client',clients[i].getId(),'has been removed');
				clients[i].removeSelf();
				clients[i] = null;
			}
		}
		clients = newclients;

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
		return {index: ind, id: id, name: name};
	}

	this.getClients = function() {
		var retval = new Array();
		for (var i in clients) {
			retval.push(clients[i].getId());
		}
		return retval;
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

	function updateVolume(params) {
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
		var lholder = $('<div>', {class: 'containerbox fixed dropdown-container'}).appendTo(j);
		$('<div>', {class: 'fixed padright'}).appendTo(lholder).html(language.gettext('snapcast_latency'));
		$('<input>', {type: 'text', class: 'fixed', name: "latency", style: "width:4em"}).appendTo(lholder);
		$('<button>', {class: "fixed"}).appendTo(lholder).html(language.gettext('snapcast_setlatency')).on("click", self.changeLatency);
		grouplist = $('<div>').appendTo(groupmenu);
		n.on('keyup', self.keyUp);
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

	this.update = function(index, data) {
		groupid = index;
		id = data.id;
		muted = data.config.volume.muted;
		volumepc = data.config.volume.percent;
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
		if (connected) {
			grouplist.empty();
			var g = snapcast.getAllGroups();
			if (g.length > 1) {
				var a = $('<div>', {class: 'menuitem textcentre'}).appendTo(grouplist);
				a.html(language.gettext('snapcast_changegroup'));
				for (var i in g) {
					if (g[i].id != groupid) {
						var d = $('<div>', {class: 'backhi clickitem textcentre', name: g[i].id}).appendTo(grouplist).on('click', self.changeGroup);
						var h = language.gettext('snapcast_group')+g[i].index;
						if (g[i].name) {
							h += ' ('+g[i].name+')';
						}
						d.html(h);
					}
				}
			}
			groupmenu.slideToggle('fast');
		}
	}

	this.changeGroup = function(e) {
		var groupid = $(this).attr('name');
		debug.log('SNAPCAST', 'Client',id,'changing group to',groupid);
		snapcast.addClientToGroup(id, groupid);
		groupmenu.slideToggle('fast');
	}

}