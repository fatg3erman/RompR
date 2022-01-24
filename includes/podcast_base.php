<?php

doPodcastBase();

print '<div class="containerbox vertical-centre is-coverable"><div class="expand"><input class="enter clearbox" id="podcastsearch" type="text" placeholder="'.language::gettext('label_searchfor').' (iTunes)" /></div>';
print '<button class="fixed searchbutton iconbutton spinable" onclick="podcasts.search()"></button>';
print '</div>';

print '<div class="fullwidth noselection clearfix is-coverable"><img id="podsclear" class="tright icon-cancel-circled podicon clickicon spinable" onclick="podcasts.clearsearch()" style="display:none;margin-bottom:4px" /></div>';
print '<div id="podcast_search" class="fullwidth noselection is-albumlist"></div>';

print '<div id="podholder" class="collectionpanel">';

print '<div id="fruitbat" class="noselection fullwidth is-albumlist">';
print '</div>';

print '</div>';

function doPodcastBase() {
	print '<div id="podcastbuttons" class="invisible toggledown is-coverable">';

	print '<div id="cocksausage">';
	print '<div class="containerbox vertical-centre"><div class="expand"><input class="enter clearbox" id="podcastsinput" type="text" placeholder="'.language::gettext("podcast_entrybox").'" /></div>';
	print '<button class="fixed iconbutton rssbutton spinable" onclick="podcasts.doPodcast(\'podcastsinput\')"></button></div>';
	print '</div>';

	print '<div class="spacer"></div>';

	print '<div class="containerbox vertical-centre noselection">';
	print '<div class="expand"><b>'.language::gettext('label_global_controls').'</b></div>';
	print '</div>';

	print '<div class="spacer"></div>';

	print '<div class="containerbox fullwidth bumpad">';
	print '<i class="icon-refresh smallicon clickable clickicon fixed tooltip podcast podglobal" name="refreshall" title="'.language::gettext('podcast_refresh_all').'"></i>';
	print '<i class="icon-headphones smallicon clickable clickicon fixed tooltip podcast podglobal" name="markalllistened" title="'.language::gettext('podcast_mark_all').'"></i>';
	print '<div class="expand"></div>';
	print '<i class="icon-trash oneeighty smallicon clickable clickicon fixed tooltip podcast podglobal" name="undeleteall" title="'.language::gettext('podcast_undelete').'"></i>';
	print '<i class="icon-download oneeighty smallicon clickable clickicon fixed tooltip podcast podglobal" name="removealldownloaded" title="'.language::gettext('podcast_removedownloaded').'"></i>';
	print '</div>';

	print '<div class="spacer"></div>';

	$sortoptions = array(
		ucfirst(strtolower(language::gettext('title_title'))) => 'Title',
		language::gettext('label_publisher') => 'Artist',
		language::gettext('label_category') => 'Category',
		language::gettext('label_new_episodes') => 'new',
		language::gettext('label_unlistened_episodes') => 'unlistened'
	);

	print '<div class="containerbox"><b>'.language::gettext('label_sortby').'</b></div>';

	for ($count = 0; $count < prefs::$prefs['podcast_sort_levels']; $count++) {
		print '<div class="containerbox vertical-centre">';
		print '<div class="selectholder expand">';
		print '<select id="podcast_sort_'.$count.'selector" class="saveomatic">';
		$options = '';
		foreach ($sortoptions as $i => $o) {
			$options .= '<option value="'.$o.'">'.$i.'</option>';
		}
		print preg_replace('/(<option value="'.prefs::$prefs['podcast_sort_'.$count].'")/', '$1 selected', $options);
		print '</select>';
		print '</div>';
		print '</div>';
		if ($count < prefs::$prefs['podcast_sort_levels']-1) {
			print '<div class="indent playlistrow2">'.language::gettext('label_then').'</div>';
		}
	}
	print '</div>';

}

?>