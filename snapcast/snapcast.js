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
					case 'Group.OnNameChanged':
					case 'Group.OnStreamChanged':
						snapcast.updateGroup(json.params);
						break;

					case 'Client.OnVolumeChanged':
					case 'Client.OnLatencyChanged':
					case 'Client.OnNameChanged':
						snapcast.updateClient(json.params);
						break;

					case 'Stream.OnUpdate':
						snapcast.updateStream(json.params);
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
			connected = false;
			if (socket) {
				socket.close();
			}
		},

		send: async function(data) {
			if (await snapsocket.initialise()) {
				try {
					socket.send(JSON.stringify(data));
				} catch (err) {
					debug.warn('SNAPSOCKET', 'Send Failed');
					socket_error();
				}
			}
		}
	}

}();

var snapcast = function() {

	var groups = {};
	var streams = {};
	var id = 0;
	var lastid = 0;
	var ew = null;

	function snapcastRequest(parms) {
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
			sleepHelper.addSleepHelper(snapsocket.close);
			sleepHelper.addWakeHelper(snapcast.updateStatus);
			snapcast.updateStatus();
		},

		updateStatus: function() {
			if (prefs.snapcast_server != '' && prefs.snapcast_http != '') {
				snapcastRequest({
					method: "Server.GetStatus"
				});
				$('#snapheader').removeClass('invisible');
			} else {
				$('#snapheader').addClass('invisible');
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
				streams = {};
				data.result.server.streams.forEach(function(s) {
					streams[s.id] = s;
				});
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
			});
		},

		setGroupMute: function(id, muted) {
			snapcastRequest({
				method: "Group.SetMute",
				params: {
					id: id,
					mute: muted
				}
			});
		},

		deleteClient: function(id) {
			snapcastRequest({
				method: "Server.DeleteClient",
				params: {
					id: id
				}
			});
		},

		setClientName: function(id, name) {
			debug.log('Chaning name of client',id,'to',name);
			snapcastRequest({
				method: "Client.SetName",
				params: {
					id: id,
					name: name
				}
			});
		},

		setGroupName: function(id, name) {
			snapcastRequest({
				method: "Group.SetName",
				params: {
					id: id,
					name: name
				}
			});
		},

		setClientLatency: function(id, latency) {
			snapcastRequest({
				method: "Client.SetLatency",
				params: {
					id: id,
					latency: parseInt(latency)
				}
			});
		},

		setStream: function(group, stream) {
			snapcastRequest({
				method: "Group.SetStream",
				params: {
					id: group,
					stream_id: stream
				}
			});
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
			});
		},

		clearEverything: function() {
			streams = {};
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
		},

		updateStream(params) {
			streams[params.id] = params.stream;
			$.each(groups, function() {
				this.group.setParams(params);
			});
		}

	}

}();

function snapcastGroup() {

	var self = this;
	var clients = {};
	var holder;
	var id = '';
	var stream_id = null;
	var muted;
	var streammenu;
	var name;
	var mutebutton;
	var lockbutton;
	var changeNameTimer;
	var volumelocked;

	function setName(data) {
		if (data.name) {
			name = data.name;
		} else {
			name = id;
		}
		holder.find('input[name="groupname"]').val(name);
		self.set_lockbutton();
	}

	function setMuted(data) {
		muted = data.muted;
		var icon = data.muted ? 'icon-output-mute' : 'icon-output';
		holder.find('i[name="groupmuted"]').removeClass('icon-output icon-output-mute').addClass(icon);
	}

	function updateStreamMenu(data) {
		if (data.stream_id) {
			stream_id = data.stream_id;
		}
		streammenu.off('change');
		streammenu.html('');
		$.each(snapcast.getAllStreams(), function() {
			$('<option>', {value: this.id}).html(streamInfo(this)).appendTo(streammenu);
		});
		streammenu.val(stream_id);
		streammenu.on('change', self.changeStream);
	}

	function streamInfo(stream) {
		var h = 'Unknown';
		// Varkious versions of snapserver provide the stream name in different ways
		if (stream.uri && stream.uri.query && stream.uri.query.name) {
			h = stream.uri.query.name;
		} else if (stream.id) {
			h = stream.id;
		} else if (stream.meta && stream.meta.STREAM) {
			h = stream.meta.STREAM;
		}

		if (stream.status)
			h += ' ('+stream.status.capitalize()+')';

		return h;
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
		var title = $('<div>', {class: 'containerbox snapgrouptitle vertical-centre'}).appendTo(holder);
		var n = $('<input>', {type: "text", class: "expand tag snapclientname", name: "groupname"}).appendTo(title);
		n.on('keyup', self.keyUp)

		var sel = $('<div>', {class: 'selectholder snapcast_select expand'}).appendTo(title);
		streammenu = $('<select>', {class: 'snapgroupstream'}).appendTo(sel);

		lockbutton = $('<i>', {class: "inline-icon fixed clickicon", name: "groupvol-locked"}).appendTo(title).on('click', self.setVolumeLock);
		self.set_lockbutton();

		mutebutton = $('<i>', {class: "inline-icon fixed clickicon", name: "groupmuted"}).appendTo(title).on('click', self.setMute);
	}

	this.set_lockbutton = function() {
		volumelocked = prefs.get_special_value('lock_'+name, false);
		let c = (volumelocked) ? 'icon-padlock' : 'icon-padlock-open';
		lockbutton.removeClass('icon-padlock').removeClass('icon-padlock-open').addClass(c);
	}

	this.setVolumeLock = function() {
		prefs.set_special_value('lock_'+name, !volumelocked);
		self.set_lockbutton();
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
				clients[c.id].client.update(id, c, self);
			} else {
				debug.log('SNAPCAST','Creating new client',c.id);
				clients[c.id] = {
					fresh: true,
					client: new snapcastClient()
				}
				clients[c.id].client.initialise(holder);
				clients[c.id].client.update(id, c, self);
			}
		});
		$.each(clients, function(i, v) {
			if (!clients[i].fresh) {
				clients[i].client.removeSelf();
				delete clients[i];
			}
		});

		updateStreamMenu(data);

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
		if (params.hasOwnProperty('stream_id') || params.hasOwnProperty('stream')) {
			updateStreamMenu(params);
		}
	}

	this.check_volume_inc = function(client_id, increment, client_volume) {
		if (!volumelocked)
			return client_volume;

		$.each(clients, function() {
			increment = this.client.check_volume_increment(increment);
		});
		if (increment != 0) {
			$.each(clients, function() {
				this.client.increment_volume(increment);
			});
		}

		return false;
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
	var parent_group;

	var holder2;
	var label_holder2;
	var second_volume;
	var second_mute;
	var second_label;

	function updateVolume(params) {
		muted = params.muted;
		volumepc = params.percent;
		volume.volumeControl("displayVolume", params.percent);
		var icon = params.muted ? 'icon-output-mute' : 'icon-output';
		holder.find('i[name="clientmuted"]').removeClass('icon-output icon-output-mute').addClass(icon);

		if (second_volume)
			second_volume.volumeControl("displayVolume", params.percent);

		if (second_mute)
			second_mute.removeClass('icon-output icon-output-mute').addClass(icon);
	}

	function updateName(g) {
		holder.find('input[name="clientname"]').val(g);
		if (second_label)
			second_label.html(g);
	}

	function updateLatency(l) {
		holder.find('input[name="latency"]').val(l);
	}

	this.initialise = function(parentdiv) {
		holder = $('<div>', {class: 'snapcastclient'}).appendTo(parentdiv);
		var title = $('<div>', {class: 'containerbox vertical-centre'}).appendTo(holder);
		var n = $('<input>', {type: "text", class: "expand tag snapclientname", name: "clientname"}).appendTo(title);
		n.on('keyup', self.keyUp);

		$('<div>', {class: 'fixed tag', name: 'notcon'}).appendTo(title);
		var client = $('<div>', {class: 'containerbox'}).appendTo(holder);
		var m = $('<i>', {class: "inline-icon fixed icon-menu clickicon"}).appendTo(title).on('click', self.setGroup);
		vc = $('<div>', {class: 'canbefaded invisible containerbox vertical-centre'}).appendTo(holder);
		volume = $('<div>', {class: 'expand playlistrow2'}).appendTo(vc);
		var m = $('<i>', {class: "inline-icon fixed clickicon", name :"clientmuted"}).appendTo(vc).on('click', self.setMute);
		volume.volumeControl({
			orientation: 'horizontal',
			command: self.setVolume
		});

		groupmenu = $('<div>', {class: 'toggledown invisible'}).insertAfter(title);
		var j = $('<div>', {class: "containerbox wrap vertical-centre"}).appendTo(groupmenu);

		var wrapper = $('<div>', {class: 'expand'}).appendTo(j);
		var sel = $('<div>', {class: 'selectholder snapcast_select'}).appendTo(wrapper);
		grouplist = $('<select>', {class: 'snapclientgroup'}).appendTo(sel);

		var lholder = $('<div>', {class: 'containerbox fixed vertical-centre'}).appendTo(j);
		$('<div>', {class: 'fixed'}).appendTo(lholder).html(language.gettext('snapcast_latency'));
		lb = $('<input>', {type: 'text', class: 'fixed', name: "latency", style: "width:4em"}).appendTo(lholder);
		lb.on('keyup', self.setLatency);

		var rb = $('<i>', {class: "fixed inline-icon icon-cancel-circled clickicon"}).appendTo(lholder).on('click', self.deleteClient);

		if ($('#snapcast-secondary').length > 0) {
			// holder2 = $('<div>', {class: 'fixed'}).insertAfter('#snapcast-secondary');
			holder2 = $('<div>', {class: 'fixed'}).appendTo('#snapcast-secondary');
			let holder3 = $('<div>', {class: 'containerbox vertical', style: 'height:100%'}).appendTo(holder2);
			let holder4 = $('<div>', {class: 'expand containerbox vertical'}).appendTo(holder3);
			second_volume = $('<div>', {class: 'expand'}).appendTo(holder4);
			second_mute = $('<i>', {class: 'fixed outhack clickicon'}).appendTo(holder4);
			second_volume.volumeControl({
				orientation: 'vertical',
				command: self.setVolume
			});
			second_mute.on('click', self.setMute);

			label_holder2 = $('<div>', {class: 'fixed snap_vert_holder'}).insertAfter(holder2);
			second_label = $('<div>', {class: 'snap_vert_text'}).appendTo(label_holder2);

		}

	}

	this.removeSelf = function() {
		holder.remove();
		if (holder2)
			holder2.remove();
		if (label_holder2)
			label_holder2.remove();
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

	this.update = function(index, data, parent) {
		groupid = index;
		id = data.id;
		parent_group = parent;
		connected = data.connected;
		if (data.config.name) {
			updateName(data.config.name)
		} else {
			updateName(data.host.name+' - '+data.host.os);
		}
		if (data.connected) {
			holder.find('div[name="notcon"]').html('');
			updateVolume(data.config.volume);
			updateLatency(data.config.latency);
			vc.removeClass('invisible');
			if (holder2) {
				holder2.removeClass('invisible');
				label_holder2.removeClass('invisible');
			}
		} else {
			holder.find('div[name="notcon"]').html(language.gettext('snapcast_notconnected'));
			if (!vc.hasClass('invisible'))
				vc.addClass('invisible');

			if (holder2 && !holder2.hasClass('invisible')) {
				holder2.addClass('invisible');
				label_holder2.addClass('invisible');
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
		let increment = v - volumepc;
		debug.log('SNAPCAST','Client',id,'setting volume to',v,increment);
		let actual = parent_group.check_volume_inc(id, increment, v);
		if (actual !== false)
			snapcast.setClientVolume(id, actual, muted);
	}

	this.check_volume_increment = function(increment) {
		let final_vol = Math.max(0, Math.min(100, volumepc+increment));
		return final_vol - volumepc;
	}

	this.increment_volume = function(inc) {
		snapcast.setClientVolume(id, volumepc+inc, muted);
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

	this.changeGroup = function() {
		var groupid = $(this).val();
		debug.log('SNAPCAST', 'Client',id,'changing group to',groupid);
		snapcast.addClientToGroup(id, groupid);
	}

}