var metaBackup = function() {

	var mbb = null;
	var monitortimer = null;
	var progressDiv;
	var doing_something = false;

	async function do_request(req, success, fail) {
		try {
			var response = await fetch(
				"api/metadata/backup/",
				{
					signal: AbortSignal.timeout(120000),
					body: JSON.stringify(req),
					cache: 'no-store',
					method: 'POST',
					priority: 'low',
				}
			);
			if (!response.ok) {
				throw new Error(response.status+' '+response.statusText);
			}
			var data = await response.json();
			success(data);
		} catch (err) {
			debug.error('METABACKUP', 'Request Failed', err);
			fail();
		}
	}

	function getBackupData() {
		do_request(
			{action: 'getbackupdata'},
			metaBackup.doMainLayout,
			function() {
				infobar.error(language.gettext('label_general_error'));
				mbb.slideToggle('fast');
			}
		);
	}

	function goDoThings(thing, what) {
		debug.info("BACKUPS",thing,what);
		doing_something = true;
		do_request(
			{action: 'backup'+thing, which: what},
			function(data) {
				clearTimeout(monitortimer);
				debug.mark("BACKUPS","Success");
				if (thing == 'restore') {
					collectionHelper.forceCollectionReload();
					podcasts.reloadList();
				}
				thing = null;
				doing_something = false;
				progressDiv.empty();
				getBackupData();
			},
			function() {
				clearTimeout(monitortimer);
				infobar.error(language.gettext('error_backupfail', [thing]));
				if (thing == 'restore') {
					collectionHelper.forceCollectionReload();
					podcasts.reloadList();
				}
				progressDiv.empty();
				getBackupData();
				doing_something = false;
			},
		);
		if (thing == 'restore') {
			setTimeout(monitorRestore, 250);
		}
	}

	function monitorRestore() {
		clearTimeout(monitortimer);
		fetch('utils/checkrestoreprogress.php')
			.then((response) => response.json())
			.then(data => {
				progressDiv.html(data.current);
				monitortimer = setTimeout(monitorRestore, 500);
			})
			.catch(err => {
				debug.warn("MONITORRESTORE","ERROR",err)
				monitortimer = setTimeout(monitorRestore, 500);
			});
	}

	return {

		open: function() {
			if (mbb === null) {
				mbb = browser.registerExtraPlugin("mbb", language.gettext("label_metabackup"), metaBackup, 'https://fatg3erman.github.io/RompR/Backing-Up-Your-Metadata');
				$("#mbbfoldup").append('<div class="noselection" style="text-align:center">'+
					'<button id="createbackup" class="fixed">'+language.gettext("button_backup")+'</button>'+
					'<div class="svg-square invisible fixed" id="backupspinner"></div>'+
					'</div>');

				progressDiv = $('<div>', {class: 'textcentre'}).appendTo('#mbbfoldup');

				$("#mbbfoldup").append('<div class="noselection fullwidth" id="mbbmunger"></div>');
				$('#createbackup').on(prefs.click_event, metaBackup.create);
				getBackupData();
			} else {
				browser.goToPlugin("mbb");
			}
		},

		doMainLayout: function(data) {

			$("#mbbmunger").empty().append('<h2>Existing Backups</h2>');
			if (data.length > 0) {
				var html = '<table class="plugin_mbb_table" align="center" cellpadding="2">';
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
					html += '<td align="center"><button class="plugclickable infoclick restore" name="'+data[i].dir+'">'+language.gettext('button_restore')+'</button></td>';
					html += '<td align="center"><i class="icon-cancel-circled inline-icon clickicon plugclickable infoclick remove" name="'+data[i].dir+'"></i></td>';
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
			$('#createbackup').off(prefs.click_event).hide();
			$('#backupspinner').css('display', 'inline-block').makeSpinner();
			do_request(
				{action: 'metabackup'},
				function(data) {
					infobar.notify(language.gettext('label_backupcreated'));
					getBackupData();
					$('#backupspinner').stopSpinner().hide();
					$('#createbackup').show().on(prefs.click_event, metaBackup.create);
				},
				function() {
					$('#backupspinner').stopSpinner().css('display', 'none');
					$('#createbackup').show().on(prefs.click_event, metaBackup.create);
					infobar.error(language.gettext('label_general_error'));
					mbb.slideToggle('fast');
				}
			);
		},

		close: function() {
			mbb = null;
		},

		handleClick: function(element, event) {
			if (element.hasClass('restore') && !doing_something) {
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
