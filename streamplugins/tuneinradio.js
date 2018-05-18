var tuneinRadioPlugin = {

    loadBigRadio: function() {
        if ($("#tuneinlist").is(':empty')) {
            $('[name="tuneinlist"]').makeSpinner();
            $("#tuneinlist").load("streamplugins/03_tuneinradio.php?populate=2", function() {
                $('[name="tuneinlist"]').stopSpinner().removeClass('icon-toggle-closed');
                if (!$('[name="tuneinlist"]').hasClass('icon-toggle-open')) {
                    $('[name="tuneinlist"]').addClass('icon-toggle-open');
                }
            });
        }
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
    }

}

menuOpeners['tuneinlist'] = tuneinRadioPlugin.loadBigRadio;
clickRegistry.addClickHandlers('#tuneinradio', tuneinRadioPlugin.handleClick, onCollectionDoubleClicked);
