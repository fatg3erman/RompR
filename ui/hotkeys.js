var shortcuts = function() {

    var key_names = {
          8: "backspace",
          9: "tab",
          10: "return",
          13: "return",
          16: "shift",
          17: "ctrl",
          18: "alt",
          19: "pause",
          20: "capslock",
          27: "esc",
          32: "space",
          33: "pageup",
          34: "pagedown",
          35: "end",
          36: "home",
          37: "left",
          38: "up",
          39: "right",
          40: "down",
          45: "insert",
          46: "del",
          59: ";",
          61: "=",
          96: "0",
          97: "1",
          98: "2",
          99: "3",
          100: "4",
          101: "5",
          102: "6",
          103: "7",
          104: "8",
          105: "9",
          106: "*",
          107: "+",
          109: "-",
          110: ".",
          111: "/",
          112: "f1",
          113: "f2",
          114: "f3",
          115: "f4",
          116: "f5",
          117: "f6",
          118: "f7",
          119: "f8",
          120: "f9",
          121: "f10",
          122: "f11",
          123: "f12",
          144: "numlock",
          145: "scroll",
          173: "-",
          186: ";",
          187: "=",
          188: ",",
          189: "-",
          190: ".",
          191: "/",
          192: "`",
          219: "[",
          220: "\\",
          221: "]",
          222: "'",
          225: "enter"
    };

    var modifiers = ['alt', 'ctrl', 'shift'];

    var hotkeys = { button_next: ".",
                    button_previous: ",",
                    button_stop: "Space",
                    button_play: "P",
                    button_volup: "=",
                    button_voldown: "-",
                    button_skipforward: "]",
                    button_skipbackward: "[",
                    button_clearplaylist: "C",
                    button_stopafter: "F",
                    button_random: "S",
                    button_crossfade: "X",
                    button_repeat: "R",
                    button_consume: "E",
                    button_rateone: "1",
                    button_ratetwo: "2",
                    button_ratethree: "3",
                    button_ratefour: "4",
                    button_ratefive: "5",
                    button_togglesources: ",",
                    button_toggleplaylist: ".",
                    config_hidebrowser: "H",
                    button_updatecollection: "U",
                    button_nextsource: "I",
    };

    var bindings = { button_next: playlist.next,
                    button_previous: playlist.previous,
                    button_stop: player.controller.stop,
                    button_play: infobar.playbutton.clicked,
                    button_volup: function() { infobar.volumeKey(5) },
                    button_voldown: function() { infobar.volumeKey(-5) },
                    button_skipforward: function() { player.skip(10) },
                    button_skipbackward: function() { player.skip(-10) },
                    button_clearplaylist: playlist.clear,
                    button_stopafter: playlist.stopafter,
                    button_random: function() { layoutProcessor.playlistControlHotKey('random') },
                    button_crossfade: function() { layoutProcessor.playlistControlHotKey('crossfade') },
                    button_repeat: function() { layoutProcessor.playlistControlHotKey('repeat') },
                    button_consume: function() { layoutProcessor.playlistControlHotKey('consume') },
                    button_rateone: function() { nowplaying.setRating(1) },
                    button_ratetwo: function() { nowplaying.setRating(2) },
                    button_ratethree: function() { nowplaying.setRating(3) },
                    button_ratefour: function() { nowplaying.setRating(4) },
                    button_ratefive: function() { nowplaying.setRating(5) },
                    button_togglesources: function() { layoutProcessor.expandInfo('left') },
                    button_toggleplaylist: function() { layoutProcessor.expandInfo('right') },
                    config_hidebrowser: function() { $("#hidebrowser").prop("checked", !$("#hidebrowser").is(':checked')); prefs.save({hidebrowser: $("#hidebrowser").is(':checked')}, hideBrowser) },
                    button_updatecollection: function(){ collectionHelper.checkCollection(true, false) },
                    button_nextsource: function() { browser.nextSource(1) }
    };

    function format_keyinput(inpname, hotkey) {
        if (hotkey === null) hotkey = "";
        return '<input id="'+inpname+'" class="tleft buttonchange clearbox" type="text" size="16" value="'+hotkey+'"></input>';
    }

    function format_clearbutton(inpname) {
        return '<td><i class="icon-cancel-circled playlisticon clickicon buttonclear" name="'+inpname+'"></i></td>';
    }

    function unbind() {
        for (var i in hotkeys) {
            if (hotkeys[i] !== "" && bindings[i]) {
                $(window).unbind('keydown', bindings[i]);
            }
        }
    }

    function getHotkeyString(event) {
        var key = key_names[event.which] || String.fromCharCode(event.which);
        var pieces = [];
        for(var i in modifiers) {
            if (event[modifiers[i]+"Key"] && modifiers[i] != key) {
                pieces.push(modifiers[i].initcaps());
            }
        }
        pieces.push(key.initcaps());
        return pieces.join('+');
    }

    return {

        load: function() {
            debug.shout("SHORTCUTS","Loading Key Bindings");
            unbind();
            for (var i in hotkeys) {
                if (localStorage.getItem('hotkeys.'+i) !== null) {
                    hotkeys[i] = localStorage.getItem('hotkeys.'+i);
                }
                if (hotkeys[i] !== "" && bindings[i]) {
                    debug.log("SHORTCUTS","Binding Key",hotkeys[i],"For",i);
                    $(window).bind('keydown', hotkeys[i], bindings[i]);
                }
            }
        },

        edit: function() {
            $("#configpanel").slideToggle('fast');
            var fnarkle = new popup({
              width: 400,
              height: 1024,
              helplink: 'https://fatg3erman.github.io/RompR/Keyboard-Shortcuts',
              title: language.gettext("title_keybindings")});
            var mywin = fnarkle.create()
            mywin.append('<table align="center" cellpadding="2" id="keybindtable" width="90%"></table>');
            for (var i in hotkeys) {
                $("#keybindtable").append('<tr><td width="50%" align="right">'+language.gettext(i).initcaps()+'</td><td>'+format_keyinput(i, hotkeys[i])+'</td></tr>');
            }
            $(".buttonchange").keydown( shortcuts.change );
            $('.buttonchange').click( shortcuts.remove );
            $('.buttonchange').hover(makeHoverWork);
            $('.buttonchange').mousemove(makeHoverWork);
            fnarkle.open();
        },

        change: function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            var key = getHotkeyString(ev);
            for (var name in hotkeys) {
                if (hotkeys[name] == key) {
                    infobar.notify(infobar.ERROR, "Key '"+key+"' is already used by '"+language.gettext(name)+"'");
                    return false;
                }
            }
            $(ev.target).val(key);
            shortcuts.save()
        },

        remove: function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            var position = getPosition(ev);
            var elemright = $(ev.target).width() + $(ev.target).offset().left;
            if (position.x > elemright - 24) {
                $(ev.target).val("");
                shortcuts.save();
            }
        },

        save: function() {
            $("#keybindtable").find(".buttonchange").each(function() {
                var k = $(this).val();
                var n = $(this).attr("id");
                hotkeys[n] = k;
                localStorage.setItem('hotkeys.'+n, k);
            });
            shortcuts.load();
        },

        add: function(name, binding, hotkey) {
            debug.log("HOTKEYS","Plugin adding key binding",name);
            hotkeys[name] = hotkey;
            bindings[name] = binding;
        }
    }

}();
