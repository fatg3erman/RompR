var tuneinRadioPlugin = {

    loadBigRadio: function() {
        if ($("#tuneinlist").is(':empty')) {
            $('[name="tuneinlist"]').makeSpinner();
            $("#tuneinlist").load("streamplugins/03_tuneinradio.php?populate=2", function() {
                $('[name="tuneinlist"]').stopSpinner().removeClass('icon-toggle-closed');
                if (!$('[name="tuneinlist"]').hasClass('icon-toggle-open')) {
                    $('[name="tuneinlist"]').addClass('icon-toggle-open');
                }
                tuneinRadioPlugin.setTheThing();
            });
        }
    },

    setTheThing: function() {
        $('[name="tuneinsearcher"]').click(function(ev){
            ev.preventDefault();
            ev.stopPropagation();
            var position = getPosition(ev);
            var elemright = $('[name="tuneinsearcher"]').width() + $('[name="tuneinsearcher"]').offset().left;
            if (position.x > elemright - 24) {
                $('[name="tuneinsearcher"]').val("");
                $('#tuneinlist').empty();
                tuneinRadioPlugin.loadBigRadio();
            }
        });
        $('[name="tuneinsearcher"]').hover(makeHoverWork);
        $('[name="tuneinsearcher"]').mousemove(makeHoverWork);
        $('[name="tuneinsearcher"]').keyup(onKeyUp);
        $('[name="sonicthehedgehog"]').click(tuneinRadioPlugin.search);
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
        } else if (clickedElement.hasClass("menu")) {
            doMenu(event, clickedElement);
        } else if (prefs.clickmode == "double") {
            if (clickedElement.hasClass("clickstream")) {
                event.stopImmediatePropagation();
                trackSelect(event, clickedElement);
            }
        } else if (prefs.clickmode == "single") {
            onCollectionDoubleClicked(event);
        }

    },
    
    browse: function(url, title, target, callback) {
        $("#"+target).load("streamplugins/03_tuneinradio.php?populate=2&url="+url+'&title='+title, function() {
            callback();
        });
    },
    
    search: function() {
        var term = $('[name="tuneinsearcher"]').val();
        debug.log("TUNEIN","Searching For",term);
        $('[name="tuneinlist"]').makeSpinner();
        $("#tuneinlist").load("streamplugins/03_tuneinradio.php?populate=2&search="+encodeURIComponent(term), function() {
            $('[name="tuneinlist"]').stopSpinner();
            tuneinRadioPlugin.setTheThing();
            if (!$('[name="tuneinlist"]').hasClass('icon-toggle-open')) {
        		$('[name="tuneinlist"]').addClass('icon-toggle-open');
        	}
        });
    }

}

menuOpeners['tuneinlist'] = tuneinRadioPlugin.loadBigRadio;
clickRegistry.addClickHandlers('#tuneinradio', tuneinRadioPlugin.handleClick, onCollectionDoubleClicked);
