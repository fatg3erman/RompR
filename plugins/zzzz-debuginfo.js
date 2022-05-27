var debugInfo = function() {

    var dbg = null;
    var info = new Array();

    const redact = [
        'lastfm_user',
        'lastfm_session_key'
    ];

    const as_date = [
        'linkchecker_nextrun',
        'next_lastfm_synctime',
        'lastversionchecktime'
    ];

    function multi_implode(ar) {
        var ret = '';
        if (typeof(ar) != 'object' || ar === null) {
            return ar;
        }
        $.each(ar, function(i, v) {
            if (typeof(v) == 'object' && v !== null) {
                ret += i+'=['+multi_implode(v)+'], ';
            } else {
                ret += i+'='+v+', ';
            }
        });
        ret = ret.substr(0, ret.length-2);
        return ret;
    }

    function getLocalInfo() {
        const t = $('#debuginfotable');
        t.append('<tr><th colspan="2">Config</th></tr>');
        for (var i in prefs) {
            if (typeof (prefs[i]) != 'function') {
                if (as_date.indexOf(i) > -1) {
                    var o = new Date(prefs[i] * 1000).toLocaleString();
                } else {
                    var o = multi_implode(prefs[i]);
                    if (redact.indexOf(i) > -1 && o !== null & o != '') {
                        o = '[Redacted]';
                    }
                }
                t.append('<tr><td>'+i+'</td><td>'+o+'</td></tr>');
            }
        }
    }

    return {

        open: function() {
            if (dbg == null) {
                dbg = browser.registerExtraPlugin("debug", language.gettext('button_debuginfo'), debugInfo);
                // randomly change the url to avoid the cache
                $('#debugfoldup').load('utils/debuginfo.php', function() {
                    $('#debugfoldup').prepend('<div class="containerbox noselection"><button class="fixed infoclick plugclickable clickcopy">Copy To Clipboard</button></div>');
                    $('#debugfoldup').prepend('<p>For information about how to report bugs, <a href="https://fatg3erman.github.io/RompR/Troubleshooting" target="_blank">'+language.gettext('config_read_the_docs')+'</a></p>');
                    $('#debugfoldup').prepend('<h3>If you are reporting a bug, appending this information to your report will be helpful</h3>');
                        getLocalInfo();
                        dbg.slideToggle('fast', function() {
                        browser.goToPlugin('debug');
                        });
                    });
            } else {
                  browser.goToPlugin("debug");
            }
        },

        close: function() {
          	dbg = null;
        },

        copyToClipboard: function() {
            var t = $('<textarea>', {id: 'debugtext'}).appendTo('body');
            var markdown = '';
            $('#debugfoldup tr').each(function() {
                var header = false;
                $(this).find('th').each(function() {
                    markdown += '* **'+$(this).html().toUpperCase()+'**\n';
                    header = true;
                });
                if (header) { return true }
                markdown += '  * ';
                $(this).find('td').each(function(i,v) {
                    if (i == 0) {
                        markdown += '**'+$(this).html()+'**';
                    } else {
                        markdown += ' '+$(this).html();
                    }
                });
                markdown += '\n';
            });

            t.val(markdown);
            const el = document.getElementById('debugtext');
            el.select();
            document.execCommand('copy');
            t.remove();
        },

        handleClick: function(element, event) {
            if (element.hasClass('clickcopy')) {
                debugInfo.copyToClipboard();
            }
        }

    }

}();

pluginManager.addPlugin(language.gettext("button_debuginfo"), debugInfo.open, null, null, true);
