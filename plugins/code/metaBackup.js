var metaBackup = function() {

	var mbb = null;

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
        		debug.log("BACKUPS","Success");
        		if (thing == 'restore') {
        			collectionHelper.forceCollectionReload();
        		}
        		getBackupData();
        	},
        	function() {
        		infobar.notify(infobar.ERROR, "Failed to "+thing+' backup');
        		if (thing == 'restore') {
        			collectionHelper.forceCollectionReload();
        		}
        		getBackupData();
        	},
        );

	}

	return {

		open: function() {
			if (mbb === null) {
	        	mbb = browser.registerExtraPlugin("mbb", language.gettext("label_metabackup"), metaBackup, 'https://fatg3erman.github.io/RompR/Backing-Up-Your-Metadata');
    			$("#mbbfoldup").append('<div class="padright noselection" style="text-align:center">'+
					'<button class="fixed" onclick="metaBackup.create()">'+language.gettext("button_backup")+'</button>'+
    				'</div>');

			    $("#mbbfoldup").append('<div class="noselection fullwidth" id="mbbmunger"></div>');
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
			metaHandlers.genericAction(
				'metabackup',
				function(data) {
            		infobar.notify(infobar.NOTIFY, "Backup Created");
            		getBackupData();
            	},
            	function() {
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
