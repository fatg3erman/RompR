var tuneinRadioPlugin = {

    loadBigRadio: function() {
        if ($("#tuneinlist").hasClass('notfilled')) {
            $('i[name="tuneinlist"]').makeSpinner();
            $("#tuneinlist").load("streamplugins/03_tuneinradio.php?populate=2", function() {
                $('i[name="tuneinlist"]').stopSpinner();
                tuneinRadioPlugin.setTheThing();
                $("#tuneinlist").removeClass('notfilled');
            });
        }
    },

    setTheThing: function() {
        // $('[name="tuneinsearcher"]').on('mouseenter',makeHoverWork);
        // $('[name="tuneinsearcher"]').on('mousemove', makeHoverWork);
        // $('[name="tuneinsearcher"]').on('keyup', onKeyUp);
        layoutProcessor.postAlbumActions();
    },

    handleClick: function(event) {
        var clickedElement = findClickableElement(event);
        if (clickedElement.hasClass("browse")) {
            event.stopImmediatePropagation();
            if (clickedElement.isClosed()) {
                clickedElement.makeSpinner();
                var url = clickedElement.prev().prev().val();
                var title = clickedElement.prev().val();
                var menutoopen = clickedElement.attr("name");
                tuneinRadioPlugin.browse(url, title, menutoopen, function() {
                    clickedElement.stopSpinner();
                    doMenu(null, clickedElement);
                    tuneinRadioPlugin.setTheThing();
                });
            } else {
                doMenu(null, clickedElement);
            }
        } else if (clickedElement.hasClass("tuneinsearchbutton")) {
            tuneinRadioPlugin.search();
        } else if (clickedElement.hasClass("menu")) {
            doMenu(event, clickedElement);
        }

    },

    browse: function(url, title, target, callback) {
        $("#"+target).load("streamplugins/03_tuneinradio.php?populate=2&url="+url+'&title='+title+'&target='+target, function() {
            callback();
        });
    },

    search: function() {
        var term = $('[name="tuneinsearcher"]').val();
        if (term == '') {
            $('#tuneinlist').empty().addClass('notfilled');
            tuneinRadioPlugin.loadBigRadio();
        } else {
            debug.log("TUNEIN","Searching For",term);
            $('i[name="tuneinlist"]').makeSpinner();
            $("#tuneinlist").load("streamplugins/03_tuneinradio.php?populate=2&search="+encodeURIComponent(term), function() {
                $('i[name="tuneinlist"]').stopSpinner();
                tuneinRadioPlugin.setTheThing();
            });
        }
    }

}

menuOpeners['tuneinlist'] = tuneinRadioPlugin.loadBigRadio;
clickRegistry.addClickHandlers('#tuneinlist', tuneinRadioPlugin.handleClick);
