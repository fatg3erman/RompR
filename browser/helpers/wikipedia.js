var wikipedia = function() {

	return {

		getLanguage: function() {
			if (lastfm.getLanguage()) {
				return lastfm.getLanguage();
			} else {
				return "en";
			}
		},

		search: async function(terms, successCallback, failCallback) {
			terms.lang = wikipedia.getLanguage();
			terms.layout = prefs.skin;
			try {
				var response = await fetch(
					'browser/backends/info_wikipedia.php',
					{
						signal: AbortSignal.timeout(30000),
						body: JSON.stringify(terms),
						cache: 'no-store',
						method: 'POST',
						priority: 'low'
					}
				);
				if (!response.ok) {
					throw new Error(response.status+' '+response.statusText);
				}
				const data = await response.text();
				successCallback(data);
			} catch (err) {
				debug.error("WIKIPEDIA", "Search request failed", err);
				failCallback(null);
			}
		},

		getFullUri: async function(terms, successCallback, failCallback) {
			terms.lang = wikipedia.getLanguage();
			terms.layout = prefs.skin;
			try {
				var response = await fetch(
					'browser/backends/info_wikipedia.php',
					{
						signal: AbortSignal.timeout(30000),
						body: JSON.stringify(terms),
						cache: 'no-store',
						method: 'POST',
						priority: 'low'
					}
				);
				if (!response.ok) {
					throw new Error(response.status+' '+response.statusText);
				}
				const data = await response.text();
				successCallback(data);
			} catch (err) {
				debug.error("WIKIPEDIA", "getFullURI request failed", err);
				failCallback(null);
			}
		},

		wikiMediaPopup: function(element, event) {
			var thing = element.attr("name");
			debug.trace("WIKIMEDIAPOPUP","Clicked element has name",thing);
			var a = thing.match(/(.*?)\/(.*)/);
			if (a && a[1] && a[2]) {
				var fname = a[2];
				if (fname.match(/jpg$/i) || fname.match(/gif$/i) || fname.match(/png$/i) || fname.match(/jpeg$/i) || fname.match(/svg$/i) || fname.match(/bmp$/i)) {
					debug.trace("WIKIMEDIAPOPUP","Clicked element has name",thing);
					imagePopup.create(element, event);
					var url = "http://"+a[1]+"/w/api.php?action=query&iiprop=url|size&prop=imageinfo&titles=" + a[2] + "&format=json&callback=?";
					debug.trace("WIKIMEDIAPOPUP","Getting", url);
					fetch(
						'browser/backends/info_wikipedia.php',
						{
							priority: 'low',
							cache: 'no-store',
							method: 'POST',
							body: JSON.stringify({json: url})
						}
					)
					.then(response => {
						if (response.ok) {
							return response.json();
						} else {
							throw new Error(response.statusText);
						}
					})
					.then(data => {
						$.each(data.query.pages, function(index, value) {
							imagePopup.create(element, event, 'getRemoteImage.php?url='+rawurlencode(value.imageinfo[0].url));
							return false;
						});
					})
					.catch(err => {
						debug.error('WIKIPEDIA', 'wikimedia fetch failed', err);
						imagePopup.close();
					})
				}
			}
			return false;
		},

		getWiki: async function(link, successCallback, failCallback) {
			$("#infopane").css({cursor:'wait'});
			$("#infopane a").css({cursor:'wait'});
			try {
				var response = await fetch(
					'browser/backends/info_wikipedia.php',
					{
						signal: AbortSignal.timeout(30000),
						body: JSON.stringify({wiki: link, layout: prefs.skin}),
						cache: 'no-store',
						method: 'POST',
						priority: 'low'
					}
				);
				if (!response.ok) {
					throw new Error(response.status+' '+response.statusText);
				}
				const data = await response.text();
				successCallback(data);
			} catch (err) {
				debug.error("WIKIPEDIA", "getWiki request failed", err);
				failCallback(null);
			}
			$("#infopane").css({cursor:'auto'});
			$("#infopane a").css({cursor:'auto'});
		},
	}

}();
