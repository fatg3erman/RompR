var player = function() {

    function playerEditor() {

        var self = this;
        var playerpu;

        function removePlayerDef(event) {
            if (decodeURIComponent($(event.target).parent().parent().attr('name')) == prefs.currenthost) {
                infobar.notify(infobar.ERROR, "You cannot delete the player you're currently using");
            } else {
                $(event.target).parent().parent().remove();
                playerpu.setWindowToContentsSize();
            }
        }

        function addNewPlayerRow() {
            $("#playertable").append('<tr class="hostdef" name="New">'+
                '<td><input type="text" size="30" name="name" value="New"/></td>'+
                '<td><input type="text" size="30" name="host" value=""/></td>'+
                '<td><input type="text" size="30" name="port" value=""/></td>'+
                '<td><input type="text" size="30" name="password" value=""/></td>'+
                '<td><input type="text" size="30" name="socket" value=""/></td>'+
                '<td><i class="icon-cancel-circled smallicon clickicon clickremhost"></i></td>'+
                '</tr>'
            );
            $('.clickremhost').off('click');
            $('.clickremhost').click(removePlayerDef);
        }

        function updatePlayerChoices() {
            var newhosts = new Object();
            var reloadNeeded = false;
            var error = false;
            $("#playertable").find('tr.hostdef').each(function() {
                var currentname = decodeURIComponent($(this).attr('name'));
                var newname = "";
                var temp = new Object();
                $(this).find('input').each(function() {
                    if ($(this).attr('name') == 'name') {
                        newname = $(this).val();
                    } else {
                        temp[$(this).attr('name')] = $(this).val();
                    }
                });

                if (newhosts.hasOwnProperty(newname)) {
                    infobar.notify(infobar.ERROR, "You cannot have two players with the same name");
                    error = true;
                }

                newhosts[newname] = temp;
                if (currentname == prefs.currenthost) {
                    if (newname != currentname) {
                        debug.log("Current Player renamed to "+newname,"PLAYERS");
                        reloadNeeded = newname;
                    }
                    if (temp.host != prefs.mpd_host || temp.port != prefs.mpd_port
                        || temp.socket != prefs.unix_socket || temp.password != prefs.mpd_password) {
                        debug.log("Current Player connection details changed","PLAYERS");
                        reloadNeeded = newname;
                    }
                }
            });
            if (error) {
                return false;
            }
            debug.log("PLAYERS",newhosts);
            if (reloadNeeded !== false) {
                prefs.save({currenthost: reloadNeeded}, function() {
                    prefs.save({multihosts: newhosts}, function() {
                        reloadWindow();
                    });
                });
            } else {
                prefs.save({multihosts: newhosts});
                self.replacePlayerOptions();
                prefs.setPrefs();
                $('[name="playerdefs"] > .savulon').click(prefs.toggleRadio);
            }
            return true;
        }

        this.edit = function() {
            $("#configpanel").slideToggle('fast');
            playerpu = new popup({
                css: {
                    width: 900,
                    height: 800
                },
                fitheight: true,
                title: language.gettext('config_players'),
                helplink: "https://fatg3erman.github.io/RompR/Using-Multiple-Players"});
            var mywin = playerpu.create();
            mywin.append('<table align="center" cellpadding="2" id="playertable" width="96%"></table>');
            $("#playertable").append('<tr><th>NAME</th><th>HOST</th><th>PORT</th><th>PASSWORD</th><th>UNIX SOCKET</th></tr>');
            for (var i in prefs.multihosts) {
                $("#playertable").append('<tr class="hostdef" name="'+escape(i)+'">'+
                    '<td><input type="text" size="30" name="name" class="notspecial" value="'+i+'"/></td>'+
                    '<td><input type="text" size="30" name="host" value="'+prefs.multihosts[i]['host']+'"/></td>'+
                    '<td><input type="text" size="30" name="port" value="'+prefs.multihosts[i]['port']+'"/></td>'+
                    '<td><input type="text" size="30" name="password" value="'+prefs.multihosts[i]['password']+'"/></td>'+
                    '<td><input type="text" size="30" name="socket" value="'+prefs.multihosts[i]['socket']+'"/></td>'+
                    '<td><i class="icon-cancel-circled smallicon clickicon clickremhost"></i></td>'+
                    '</tr>'
                );
            }
            var buttons = $('<div>',{class: "pref clearfix"}).appendTo(mywin);
            var add = $('<i>',{class: "icon-plus smallicon clickicon tleft"}).appendTo(buttons);
            add.click(function() {
                addNewPlayerRow();
                playerpu.setWindowToContentsSize();
            });
            var c = $('<button>',{class: "tright"}).appendTo(buttons);
            c.html(language.gettext('button_cancel'));
            playerpu.useAsCloseButton(c, false);

            var d = $('<button>',{class: "tright"}).appendTo(buttons);
            d.html(language.gettext('button_OK'));
            playerpu.useAsCloseButton(d, updatePlayerChoices);

            $('.clickremhost').off('click');
            $('.clickremhost').click(removePlayerDef);

            $(document).on('keyup', 'input.notspecial', function() {
                debug.log("ENTER","Value Changed");
                this.value = this.value.replace(/[\*&\+\s<>\[\]:;,\.\(\)]/g, '');
            });

            playerpu.open();
        }

        this.replacePlayerOptions = function() {
            $('[name="playerdefs"]').each(function(index) {
                $(this).empty();
                for (var i in prefs.multihosts) {
                    $(this).append('<input type="radio" class="topcheck savulon" name="currenthost_duplicate'+index+'" value="'+
                        i+'" id="host_'+escape(i)+index+'">'+
                        '<label for="host_'+escape(i)+index+'">'+i+'</label><br/>');
                }
            });
        }
    }

    return {

        // These are all the mpd status fields the program currently cares about.
        // We don't need to initialise them here; this is for reference
        status: {
        	file: null,
        	bitrate: null,
        	audio: null,
        	state: null,
        	volume: -1,
        	song: -1,
        	elapsed: 0,
        	songid: 0,
        	consume: 0,
        	xfade: 0,
        	repeat: 0,
        	random: 0,
        	error: null,
        	Date: null,
        	Genre: null,
        	Title: null,
        },

        urischemes: new Object(),

        collectionLoaded: false,

        updatingcollection: false,

        collection_is_empty: true,

        controller: new playerController(),

        defs: new playerEditor(),

        canPlay: function(urischeme) {
            return this.urischemes.hasOwnProperty(urischeme);
        },

        skip: function(sec) {
            if (this.status.state == "play") {
                var p = infobar.progress();
                var to = p + sec;
                if (p < 0) p = 0;
                this.controller.seek(to);
            }
        },

    }

}();
