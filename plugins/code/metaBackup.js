var metaBackup = function() {

	var mbb = null;
	var monitortimer = null;
	var progressDiv;

	function getBackupData() {
		metaHandlers.genericAction(
			'getbackupdata',
			metaBackup.doMainLayout,
			function() {
        		infobar.notify(infobar.ERROR, "Failed to get Backup info");
        		mbb.slideToggle('fast');
        	}
        );
	}

	function goDoThings(thing, what) {
		debug.mark("BACKUPS",thing,what);
		metaHandlers.genericAction(
			[{action: 'backup'+thing, which: what}],
        	function(data) {
				clearTimeout(monitortimer);
        		debug.log("BACKUPS","Success");
        		if (thing == 'restore') {
        			collectionHelper.forceCollectionReload();
        		}
				progressDiv.empty();
        		getBackupData();
        	},
        	function() {
				clearTimeout(monitortimer);
        		infobar.notify(infobar.ERROR, "Failed to "+thing+' backup');
        		if (thing == 'restore') {
        			collectionHelper.forceCollectionReload();
        		}
				progressDiv.empty();
        		getBackupData();
        	},
        );
		if (thing == 'restore') {
			setTimeout(monitorRestore, 250);
		}
	}

	function monitorRestore() {
		clearTimeout(monitortimer);
		$.ajax({
            type: "GET",
            url: 'utils/checkrestoreprogress.php',
            dataType: 'json',
            success: function(data) {
                debug.debug("UPDATE",data);
                progressDiv.html(data.current);
                monitortimer = setTimeout(monitorRestore, 250);
            },
            error: function(data) {
                debug.log("UPDATE","ERROR",data);
				monitortimer = setTimeout(monitorRestore, 250);
            }
        });
	}

	return {

		open: function() {
			if (mbb === null) {
	        	mbb = browser.registerExtraPlugin("mbb", language.gettext("label_metabackup"), metaBackup, 'https://fatg3erman.github.io/RompR/Backing-Up-Your-Metadata');
    			$("#mbbfoldup").append('<div class="padright noselection" style="text-align:center">'+
					'<button id="createbackup" class="fixed">'+language.gettext("button_backup")+'</button>'+
					'<div class="svg-square invisible fixed" id="backupspinner"></div>'+
    				'</div>');

				progressDiv = $('<div>', {class: 'padright', style: 'text-align:center'}).appendTo('#mbbfoldup');

			    $("#mbbfoldup").append('<div class="noselection fullwidth" id="mbbmunger"></div>');
				$('#createbackup').on('click', metaBackup.create);
			    getBackupData();
			} else {
				browser.goToPlugin("mbb");
			}
		},

		doMainLayout: function(data) {

			$("#mbbmunger").empty().append('<h2>Existing Backups</h2>');
			if (data.length > 0) {
				var html = '<table class="backuptable" align="center" cellpadding="2">';
				html += '<tr><th>Backup Date</th>';
				for (var i in data[0].stats) {
					html += '<th>'+i+'</th>';
				}
				html += '<th></th><th></th>';
				html += '</tr>';
				for (var i in data) {
					html += '<tr><td>'+data[i].name+'</td>';
					for (var j in data[i].stats) {
						html += '<td>'+data[i].stats[j]+'</td>';
					}
					html += '<td align="center"><button class="plugclickable infoclick restore" name="'+data[i].dir+'">Restore</button></td>';
					html += '<td align="center"><i class="icon-cancel-circled playlisticon clickicon plugclickable infoclick remove" name="'+data[i].dir+'"></i></td>';
					html += '</tr>';
				}
				html += '</table>';
				$("#mbbmunger").append(html);
			}
            if (!$("#mbbfoldup").is(':visible')) {
	            mbb.slideToggle('fast', function() {
		        	browser.goToPlugin("mbb");
	            });
	        }
		},

		create: function() {
			$('#createbackup').off('click').hide();
			$('#backupspinner').css('display', 'inline-block').makeSpinner();
			metaHandlers.genericAction(
				'metabackup',
				function(data) {
            		infobar.notify(infobar.NOTIFY, "Backup Created");
            		getBackupData();
					$('#backupspinner').stopSpinner().hide();
					$('#createbackup').show().on('click', metaBackup.create);
            	},
            	function() {
					$('#backupspinner').stopSpinner().css('display', 'none');
					$('#createbackup').show().on('click', metaBackup.create);
            		infobar.notify(infobar.ERROR, "Failed to get Backup info");
            		mbb.slideToggle('fast');
            	}
            );
	    },

		close: function() {
			mbb = null;
		},

		handleClick: function(element, event) {
			if (element.hasClass('restore')) {
				collectionHelper.prepareForLiftOff('Restoring Data');
				goDoThings('restore',element.attr("name"));
			} else if (element.hasClass('remove')) {
				goDoThings('remove',element.attr("name"));
			}
		}
	}

}();

pluginManager.setAction(language.gettext("label_metabackup"), metaBackup.open);
metaBackup.open();
