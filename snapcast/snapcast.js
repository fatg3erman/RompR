var snapcast = function() {

    var groups = new Array();
    var streams = new Array();
    var updatetimer = null;
    var id = 0;
    var lastid = 0;
    var ew = null;

    function snapcastRequest(parms, callback) {
        clearTimeout(updatetimer);
        id++;
        parms.id = id;
        parms.jsonrpc = "2.0";
        $.ajax({
            type: 'POST',
            url: 'snapcast/snapapi.php',
            data: JSON.stringify(parms),
            success: callback,
            error: function(jqXHR, textStatus, errorThrown) {
                debug.error("SNAPCAST","Command Failed",parms,textStatus,errorThrown);
                callback({error: 'Could not contact Snapcast server'});
            }
        })
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
        updateStatus: function() {
            if (prefs.snapcast_server != '' && prefs.snapcast_port != '') {
                snapcastRequest({
                    method: "Server.GetStatus"
                }, snapcast.gotStatus);
                $('#snapheader').removeClass('invisible');
            }
        },

        gotStatus: function(data) {
            clearTimeout(updatetimer);
            debug.log("SNAPCAST", "Server Status", data);
            if (data.hasOwnProperty('error') && lastid != id) {
                if (ew === null) {
                    ew = $('<div>', {class: "fullwidth textcentre", style: "padding:4px"}).insertBefore('#snapcastgroups');
                    $('#snapcastgroups').addClass('canbefaded').css({opacity: '0.4'});
                }
                ew.html('<b>'+data.error+'</b>');
            } else if (data.hasOwnProperty('id') && parseInt(data.id) == id) {
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
                        debug.log('SNAPCAST','Updating group',data.result.server.groups[i].id);
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
                debug.shout('SNAPCAST','Got response with id',data.id,'expecting',id);
            }
            updatetimer = setTimeout(snapcast.updateStatus, 60000);
        },

        streamInfo: function(streamid) {
            for (var i in streams) {
                if (streams[i].id == streamid) {
                    var h = streams[i].status.capitalize()+' ';
                    h += streams[i].meta.STREAM;
                    h += ' <span class="snapclienthost">('+streams[i].uri.query.codec+' '+streams[i].uri.query.sampleformat+')</span>';
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
            }, snapcast.updateStatus);
        },

        setGroupMute: function(id, muted) {
            snapcastRequest({
                method: "Group.SetMute",
                params: {
                    id: id,
                    mute: muted
                }
            }, snapcast.updateStatus);
        },

        deleteClient: function(id) {
            snapcastRequest({
                method: "Server.DeleteClient",
                params: {
                    id: id
                }
            }, snapcast.gotStatus);
        },

        setClientName: function(id, name) {
            snapcastRequest({
                method: "Client.SetName",
                params: {
                    id: id,
                    name: name
                }
            }, snapcast.updateStatus);
        },

        setClientLatency: function(id, latency) {
            snapcastRequest({
                method: "Client.SetLatency",
                params: {
                    id: id,
                    latency: parseInt(latency)
                }
            }, snapcast.updateStatus);
        },

        setStream: function(group, stream) {
            snapcastRequest({
                method: "Group.SetStream",
                params: {
                    id: group,
                    stream_id: stream
                }
            }, snapcast.updateStatus);
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
            }, snapcast.gotStatus);
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

    function findClient(arr, id) {
        for (var i in arr) {
            if (arr[i].getId() == id) {
                return i;
            }
        }
        return false;
    }

    this.initialise = function() {
        holder = $('<div>', {class: 'snapcastgroup fullwidth'}).appendTo('#snapcastgroups');
        var title = $('<div>', {class: 'containerbox snapgrouptitle dropdown-container'}).appendTo(holder);
        title.append('<div class="fixed tag" name="groupname"></div>');
        title.append('<div class="expand tag textcentre" name="groupstream"></div>');
        var m = $('<i>', {class: "playlisticonr fixed icon-menu"}).appendTo(title).on('click', self.setStream);
        m = $('<i>', {class: "playlisticonr fixed", name: "groupmuted"}).appendTo(title).on('click', self.setMute);
        streammenu = $('<div>', {class: 'toggledown invisible'}).insertAfter(title);
    }

    this.removeSelf = function() {
        holder.remove();
    }

    this.getId = function() {
        return id;
    }

    this.update = function(index, data) {
        ind = index;
        id = data.id;
        muted = data.muted;
        name = data.name;
        var g = 'Group '+index;
        if (data.name) {
            g += ' ('+data.name+')';
        }
        holder.find('div[name="groupname"]').html(g);
        holder.find('div[name="groupstream"]').html(snapcast.streamInfo(data.stream_id));
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
                debug.log('SNAPCAST','Updating client',data.clients[i].id);
                newclients.push(clients[client_exists]);
                clients[client_exists].update(id, data.clients[i]);
            }
        }
        // Find removed groups
        for (var i in clients) {
            if (findClient(newclients, clients[i].getId()) === false) {
                debug.log('SNAPCAST','Client',clients[i].getId(),'has been removed');
                clients[i].removeSelf();
                clients[i] = null;
            }
        }
        clients = newclients;
    }

    this.setMute = function() {
        snapcast.setGroupMute(id, !muted);
    }

    this.setStream = function() {
        streammenu.empty();
        var s = snapcast.getAllStreams();
        var a = $('<div>', {class: 'menuitem textcentre'}).appendTo(streammenu);
        a.html('Change Stream');
        for (var i in s) {
            var d = $('<div>', {class: 'backhi clickitem', name: s[i].id}).appendTo(streammenu).on('click', self.changeStream);
            d.html(snapcast.streamInfo(s[i].id));
        }
        streammenu.slideToggle('fast');
    }

    this.changeStream = function(e) {
        var streamid = $(this).attr('name');
        debug.log('SNAPCAST', 'Group',id,'changing stream to',streamid);
        snapcast.setStream(id, streamid);
        streammenu.slideToggle('fast');
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
    var namesavetimer = null;
    var latencysavetimer = null;
    var groupmenu;
    var grouplist;
    var groupid;

    this.initialise = function(parentdiv) {
        holder = $('<div>', {class: 'snapcastclient'}).appendTo(parentdiv);
        var title = $('<div>', {class: 'containerbox dropdown-container'}).appendTo(holder);
        var n = $('<input>', {type: "text", class: "expand tag snapclientname", name: "clientname"}).appendTo(title).on('keyup', self.changeName);
        title.append('<div class="expand snapclienthost" name="clienthost"></div>');
        var m = $('<i>', {class: "playlisticonr fixed icon-menu"}).appendTo(title).on('click', self.setGroup);
        var rb = $('<i>', {class: "fixed playlisticonr icon-cancel-circled"}).appendTo(title).on('click', self.deleteClient);
        vc = $('<div>', {class: 'containerbox dropdown-container invisible'}).appendTo(holder);
        volume = $('<div>', {class: 'expand snapclienthost'}).appendTo(vc);
        var m = $('<i>', {class: "podicon fixed", name :"clientmuted"}).appendTo(vc).on('click', self.setMute);
        volume.volumeControl({
            orientation: 'horizontal',
            command: self.setVolume
        });
        groupmenu = $('<div>', {class: 'toggledown invisible'}).insertAfter(title);
        var j = $('<div>', {class: "containerbox dropdown-container"}).appendTo(groupmenu);
        j.append('<div class="fixed padright">Latency</div>');
        var k = $('<input>', {type: 'text', class: 'expand tag', name: "latency"}).appendTo(j).on('keyup', self.changeLatency);
        grouplist = $('<div>').appendTo(groupmenu);
    }

    this.removeSelf = function() {
        holder.remove();
    }

    this.getId = function() {
        return id;
    }

    this.update = function(index, data) {
        groupid = index;
        id = data.id;
        muted = data.config.volume.muted;
        volumepc = data.config.volume.percent;
        var g = '';
        if (data.config.name) {
            g = data.config.name;
        } else {
            g = id;
        }
        if (!data.connected) {
            g += ' (Not Connected)';
        }
        var h = '('+data.host.name+' - '+data.host.os+')';
        holder.find('input[name="clientname"]').val(g);
        holder.find('div[name="clienthost"]').html(h);
        if (data.connected) {
            volume.volumeControl("displayVolume", data.config.volume.percent);
            var icon = data.config.volume.muted ? 'icon-output-mute' : 'icon-output';
            holder.find('i[name="clientmuted"]').removeClass('icon-output icon-output-mute').addClass(icon);
            holder.find('input[name="latency"]').val(data.config.latency);
            vc.removeClass('invisible');
        } else if (!vc.hasClass('invisible')) {
            vc.addClass('invisible');
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
        clearTimeout(namesavetimer);
        namesavetimer = setTimeout(self.saveNameChange, 1000);
    }

    this.saveNameChange = function() {
        snapcast.setClientName(id, holder.find('input[name="clientname"]').val());
    }

    this.changeLatency = function() {
        clearTimeout(latencysavetimer);
        latencysavetimer = setTimeout(self.saveLatencyChange, 1000);
    }

    this.saveLatencyChange = function() {
        snapcast.setClientLatency(id, holder.find('input[name="latency"]').val());
    }

    this.setGroup = function() {
        grouplist.empty();
        var g = snapcast.getAllGroups();
        if (g.length > 1) {
            var a = $('<div>', {class: 'menuitem textcentre'}).appendTo(grouplist);
            a.html('Change Group');
            for (var i in g) {
                if (g[i].id != groupid) {
                    var d = $('<div>', {class: 'backhi clickitem', name: g[i].id}).appendTo(grouplist).on('click', self.changeGroup);
                    var h = 'Group '+g[i].index;
                    if (g[i].name) {
                        h += ' ('+g[i].name+')';
                    }
                    d.html(h);
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