<?php

// The first term here is the name that will appear in the drop-down list
// This has the form $langname['file_name without .php extension'] = "Display Name";
// Try to name your file as the two-letter language code so RompR can pick a suitable
// default language automatically.

$langname['fr'] = "Français";

$languages['fr'] = array (

	// The Sources Chooser Button tooltips
	"button_local_music" => "Musique",
	"button_file_browser" => "Parcourir",
	"button_internet_radio" => "Radios Internet",
	"button_albumart" => "Gestion De Pochettes",

	// Tooltips for Buttons across the top of the information panel
	"button_togglesources" => "Parcours Des Sources",
	"button_back" => "Retour",
	"button_history" => "Historique",
	"button_forward" => "Suivant",
	"button_toggleplaylist" => "Parcours Des Playlists",

	// Tooltips for playlist buttons
	"button_prefs" => "Préférences",
	"button_clearplaylist" => "Vider Playlist",
	"button_loadplaylist" => "Charger Playlist",
	"button_saveplaylist" => "Sauver Playlist",

	// Tooltips for playback controls
	"button_previous" => "Morceau Précédent",
	"button_play" => "Play/Pause",
	"button_stop" => "Stop",
	"button_stopafter" => "Stop A La Fin Du Morceau",
	"button_next" => "Suivant",
	"button_love" => "J'Aime",
	"button_ban" => "Bannir",
	"button_volume" => "Régler le volume",

	// Configuration menu entries
	"config_language" => "LANGAGE",
	"config_theme" => "THEME",
	"config_hidealbumlist" => "Masquer Liste Musicale Locale",
	"config_hidefileslist" => "Masque Liste Fichiers",
	"config_hidelastfm" => "Masquer Stations Last.FM",
	"config_hideradio" => "Masquer Stations Radio",
	"config_fullbio" => "Récupérer les biographies complètes depuis Last.FM",
	"config_lastfmlang" => "Langues pour Last.FM et Wikipedia",
	"config_lastfmdefault" => "Langue par défaut (English)",
	"config_lastfminterface" => "Langue de l'interface RompR",
	"config_lastfmbrowser" => "Langue par défaut du navigateur",
	"config_lastfmlanguser" => "Cette langue spécifique:",
	"config_langinfo" => "Last.FM et Wikipedia utiliseront l'anglais si il n'y a pas l'information dans votre langue",
	"config_autoscroll" => "Auto-Scrolle la playlist jusqu'à la piste courante",
	"config_autocovers" => "Télécharge automatiquement les pochettes",
	"config_musicfolders" => "Pour utiliser les pochettes de vos dossiers musique, indiquer les chemins des dossiers ici :",
	"config_crossfade" => "Durée du fondu (secondes)",
	"config_clicklabel" => "Propriété Du Clic De Sélection De Musique",
	"config_doubleclick" => "Double-clic pour ajout, Click pour sélection",
	"config_singleclick" => "Click pour ajout, pas de sélection",
	"config_sortbydate" => "Classement Des Albums Par Date",
	"config_notvabydate" => "Pas De Classement Par Date Pour 'Various Artists'",
	"config_updateonstart" => "Mise A Jour De La Musique Locale Au Démarrage",
	"config_updatenow" => "Mise A Jour De La Musique Locale",
	"config_rescan" => "Scan Complet De La Musique Locale",
	"config_editshortcuts" => "Edition Des Raccourcis Clavier...",
	"config_audiooutputs" => "Sorties Audio",
	"config_lastfmusername" => "Utilisateur Last.FM",
	"config_loginbutton" => "Connexion",
	"config_scrobbling" => "Scrobbling Last.FM Activé",
	"config_scrobblepercent" => "Pourcentage de pistes à jouer avec de scrobbler",
	"config_autocorrect" => "Auto-correction Last.FM Activée",
	"config_tagloved" => "Tagguer Pistes Aimées Avec:",
	"config_country" => "PAYS (pour Spotify)",

	// Various buttons for the playlist dropdowns
	"button_imsure" => "Je Suis Sûr",
	"button_save" => "Sauver",

	// General Labels and buttons in the main layout
	"label_lastfm" => "Last.FM",
	"button_searchmusic" => "Recherche De Musique",
	"button_searchfiles" => "Recherche De Fichier",
	"label_yourradio" => "Vos Stations Radio",
	"label_podcasts" => "Podcasts",
	"label_somafm" => "Soma FM",
	"label_bbcradio" => "Live BBC Radio",
	"label_icecast" => "Icecast Radio",
	"label_emptyinfo" => "Panneau d'information concernant la musique en cours de lecture",
	"button_playlistcontrols" => "Contrôle De La Playlist",
	"button_random" => "ALÉATOIRE",
	"button_crossfade" => "FONDU",
	"button_repeat" => "RÉPÉTITION",
	"button_consume" => "CONSOMMER",
	"label_yes" => "Oui",
	"label_no" => "Non",
	"label_updating" => "Mise A Jour Des Fichiers Locaux",
	"label_update_error" => "Echec de la génération de la liste de musique locale !",
	"label_notsupported" => "Opération non supportée !",
	"label_playlisterror" => "Quelque chose s'est mal passé dans la récupération de la liste de lecture !",
	"label_downloading" => "Téléchargement ...",
	"button_OK" => "OK",
	"button_cancel" => "Annuler",
	"error_playlistname" => "Le nom de la liste de lecture comprend des '/'",
	"label_savedpl" => "Liste de lecture sauvée comme %s",
	"label_loadingstations" => "Chargement des stations...",

	// Search Forms
	"label_searchfor" => "Rechercher ...",
	"label_searching" => "Recherche en cours...",
	"button_search" => "Recherche",
	"label_searchresults" => "Résultats",
	"label_multiterms" => "Plusieurs termes peuvent être utilisés simultanément",
	"label_limitsearch" => "Limite Des Recherches",

	// General multipurpose labels
	"label_tracks" => "pistes",
	"label_albums" => "albums",
	"label_artists" => "artistes",
	"label_track" => "Piste",
	"label_album" => "Album",
	"label_artist" => "Artiste",
	"label_anything" => "Tout",
	"label_discogs" => "Discogs",
	"label_musicbrainz" => "Musicbrainz",
	"label_wikipedia" => "Wikipedia",
	"label_general_error" => "Il y a eu une erreur. Merci de rafraîchir cette page et d'essayer à nouveau",
	"label_days" => "jours",
	"label_hours" => "heures",
	"label_minutes" => "minutes",
	"label_noalbums" => "Aucun Albums Trouvé",
	"label_notracks" => "Aucune Piste Trouvée",
	"label_duration" => "Durée",
	"label_playererror" => "Erreur Du Lecteur",
	"label_tunefailed" => "Échec De Lecture De La Station De Radio",
	"label_noneighbours" => "Aucun voisin trouvé",
	"label_nofreinds" => "Vous avez 0 amis",
	"label_notags" => "Aucun tag trouvé",
	"label_noartists" => "Aucun artiste trouvé",
	"mopidy_tooold" => "Votre version de Mopidy est trop vieille. Merci de mettre à jour vers la version %s ou supérieure",
	"button_playradio" => "Lecture",

	// Playlist and Now Playing
	"label_waitingforstation" => "Attente des informations de cette station...",
	"label_notforradio" => "Non supporté pour les flux radio",
	"label_incoming" => "Entrant...",
	"label_addingtracks" => "Ajouter Des Pistes",
	// Now Playing - [track name] by [artist] on [album]
	"label_by" => "par",
	"label_on" => "dans",
	// Now playing - 1:45 of 6:50
	"label_of" => "de",

	// Podcasts
	"podcast_rss_error" => "Échec de traitement du flux RSS",
	"podcast_remove_error" => "Échec de suppression du podcast",
	"podcast_general_error" => "Échec de l’opération :(",
	"podcast_entrybox" => "Entrez l'URL d'un podcast flux RSS feed ici, ou déplacez son icône",
	// Podcast tooltips
	"podcast_delete" => "Supprimer ce Podcast",
	"podcast_configure" => "Configurer ce Podcast",
	"podcast_refresh" => "Rafraîchir ce Podcast",
	"podcast_download_all" => "Télécharger tous les épisodes de ce Podcast",
	"podcast_mark_all" => "Marquer Tous les Épisodes comme Écoutés",
	// Podcast display options
	"podcast_display" => "Afficher",
	"podcast_display_all" => "Tout",
	"podcast_display_onlynew" => "Nouveaux Seulement",
	"podcast_display_unlistened" => "Nouveaux et Non Écoutes",
	"podcast_display_downloadnew" => "Nouveaux et Téléchargés",
	"podcast_display_downloaded" => "Seulement Les Téléchargés",
	// Podcast refresh options
	"podcast_refresh" => "Rafraîchir",
	"podcast_refresh_never" => "Manuellement",
	"podcast_refresh_hourly" => "Toutes les Heures",
	"podcast_refresh_daily" => "Tous les Jours",
	"podcast_refresh_weekly" => "Toutes les Semaines",
	"podcast_refresh_monthly" => "Tous les Mois",
	// Podcast auto expire
	"podcast_expire" => "Conserver les Épisodes Pendant",
	"podcast_expire_tooltip" => "Tout épisode plus ancien que cette durée sera supprimé de la liste. Les changements de cette option prendront effet au prochain rafraîchissement du podcast",
	"podcast_expire_never" => "Toujours",
	"podcast_expire_week" => "Une Semaine",
	"podcast_expire_2week" => "Deux Semaines",
	"podcast_expire_month" => "Un Mois",
	"podcast_expire_2month" => "Deux Mois",
	"podcast_expire_6month" => "Six Mois",
	"podcast_expire_year" => "Uné Année",
	// Podcast number to keep
	"podcast_keep" => "Nombre De Podcasts A Conserver",
	"podcast_keep_tooltip" => "La liste ne montrera que ce nombre d'épisodes. Les changements de cette option prendront effet au prochain rafraîchissement du podcast",
	"podcast_keep_0" => "Pas de limitation",
	// Podcast other options
	"podcast_keep_downloaded" => "Conserver tous les épisodes téléchargés",
	"podcast_kd_tooltip" => "Activer cette option pour conserver tous les épisodes téléchargés. Les deux options précédentes ne s'appliqueront qu'aux épisodes déjà téléchargés",
	"podcast_auto_download" => "Téléchargement Automatique Des Nouveaux Épisodes",
	"podcast_tooltip_new" => "Ceci est un nouvel épisode",
	"podcast_tooltip_notnew" => "Cet épisode n'est pas nouveau, mais il n'a pas été écouté",
	"podcast_tooltip_downloaded" => "Cet épisode a été téléchargé",
	"podcast_tooltip_download" => "Télécharger cet épisode sur votre ordinateur",
	"podcast_tooltip_mark" => "Marquer comme écouté",
	"podcast_tooltip_delepisode" => "Supprimer cet épisode",
	"podcast_expired" => "Cet épisode a expiré",
	// eg 2 days left to listen
	"podcast_timeleft" => "%s jours restants pour écouter",

	// Soma FM Chooser Panel
	"label_soma" => "Soma.FM est une radio musicale, non commerciale, diffusée depuis San Francisco exclusivement via internet",
	"label_soma_beg" => "Donations bienvenues, si vous aimez ces stations",

	// Your radio stations
	"label_radioinput" => "Entrez l'URL",

	//Album Art Manager
	"albumart_title" => "Pochettes de Disques",
	"albumart_getmissing" => "Trouver les Pochettes Manquantes",
	"albumart_showall" => "Montrer Toutes les Pochettes",
	"albumart_instructions" => "Sélectionner une pochette pour la changer, ou déplacer une image de votre disque dur ou d'une autre fenêtre du navigateur",
	"albumart_onlyempty" => "Montrer Uniquement Les Albums Sans Pochettes",
	"albumart_allartists" => "Tous Les Artistes",
	"albumart_unused" => "Images Non Utilisées",
	"albumart_deleting" => "Suppression...",
	"albumart_error" => "Ca n'a pas fonctionné",
	"albumart_googlesearch" => "Recherche Google",
	"albumart_local" => "Images Locales",
	"albumart_upload" => "Envoi De Fichier",
	"albumart_uploadbutton" => "Envoi",
	"albumart_newtab" => "Recherche Google Dans Un Nouvel Onglet",
	"albumart_dragdrop" => "Vous pouvez glisser-déposer des images depuis votre disque dur ou depuis une autre fenêtre du navigateur directement par dessus l'image (dans la plupart des navigateurs)",
	"albumart_showmore" => "Montrer Plus De Résultats",
	"albumart_googleproblem" => "Google dit qu'il y a eu un problème",
	"albumart_getthese" => "Récupérer Ces Pochettes",
	"albumart_deletethese" => "Supprimer Ces Pochettes",
	"albumart_nocollection" => "Veuillez créer votre bibliothèque musicale avant d'essayer de télécharger des pochettes",
	"albumart_nocovercount" => "albums sans pochette",
	"albumart_getting" => "Récupération",

	// Setup page (rompr/?setup)
	"setup_connectfail" => "Rompr n'a pas réussi à se connecter à un serveur mpd ou mopidy",
	"setup_connecterror" => "Il y a eu un problème de communication avec votre serveur mpd ou mopidy : ",
	"setup_request" => "Vous avez demandé la page de configuration",
	"setup_labeladdresses" => "Veuillez entrer l'adresse IP et le port de votre serveur mpd dans ce formulaire",
	"setup_addressnote" => "Note: localhost dans ce contexte signifie que votre ordinateur fait tourner un serveur apache",
	"setup_ipaddress" => "Adresse IP ou nom d'hôte",
	"setup_port" => "Port",
	"setup_advanced" => "Options avancées",
	"setup_leaveblank" => "Laissez les vides à moins que vous n'en ayez utilité",
	"setup_password" => "Mot de passe",
	"setup_unixsocket" => "socket UNIX",

	// Intro Window
	"intro_title" => "Informations Concernant Cette Version",
	"intro_welcome" => "Bienvenue dans RompR version",
	"intro_viewingmobile" => "Vous voyez la version mobile de RompR. Pour voir la version standard, allez à",
	"intro_viewmobile" => "Pour voir la version mobile, allez à",
	"intro_basicmanual" => "Le Manuel de Base de RompR est là:",
	"intro_forum" => "Le Forum de discussion est là:",
	"intro_mopidy" => "IMPORTANT Informations Pour les Utilisateurs de Mopidy",
	"intro_mopidywiki" => "Si vous utilisez Mopidy, merci de lire le Wiki",
	"intro_mopidyversion" => "Vous devez utiliser Mopidy %s ou postérieur",

	// Last.FM
	"lastfm_loginwindow" => "Connexion à Last.FM",
	"lastfm_login1" => "Cliquez le bouton ci-dessous opur ouvrir le site Last.FM dans un nouvel onglet. Entrez votre identifiant Last.FM si nécessaire et donnez la permission d'accès à RompR",
	"lastfm_login2" => "Vous pouvez fermer le nouvel onglet une fois terminé, mais ne fermez pas cette fenêtre !",
	"lastfm_loginbutton" => "Cliquez Ici Pour Connecter",
	"lastfm_login3" => "Une fois connecté sur Last.FM, cliquer sur le bouton OK ci-dessous pour terminer le processus",
	"lastfm_loginfailed" => "Échec de connexion à Last.FM",
	"label_loved" => "J'Aime",
	"label_lovefailed" => "Échec De Marquage J'aime",
	"label_unloved" => "Je N'Aime Plus",
	"label_unlovefailed" => "Échec De Suppresion Du J'Aime",
	"label_banned" => "Banni",
	"label_banfailed" => "Échec De Banissement",
	"label_scrobbled" => "Scrobblé",
	"label_scrobblefailed" => "Échec de scrobble",

	// Info Panel
	"info_gettinginfo" => "Récupération Des Infos...",
	"info_newtab" => "Voir Dans Un Nouvel Onglet",

	// File Info panel
	"button_fileinfo" => "Informations (Ficher)",
	"info_file" => "Fichier:",
	"info_from_beets" => "(des serveurs beets)",
	"info_format" => "Format:",
	"info_bitrate" => "Debit:",
	"info_samplerate" => "Échantillonnage:",
	"info_mono" => "Mono",
	"info_stereo" => "Stéreo",
	"info_channels" => "Canaux",
	"info_date" => "Date:",
	"info_genre" => "Genre:",
	"info_performers" => "Interprète:",
	"info_composers" => "Compositeurs:",
	"info_comment" => "Commentaire:",
	"info_label" => "Label:",
	"info_disctitle" => "Titre Du Disque:",
	"info_encoder" => "Encodeur:",
	"info_year" => "Année:",

	// Last.FM Info Panel
	"button_infolastfm" => "Informations (Last.FM)",
	"label_notrackinfo" => "Pas d'information trouvée sur cette piste",
	"label_noalbuminfo" => "Pas d'information trouvée sur cet album",
	"label_noartistinfo" => "Pas d'information trouvée sur cet artiste",
	"lastfm_listeners" => "Auditeurs:",
	"lastfm_plays" => "Lectures:",
	"lastfm_yourplays" => "Vos Lectures:",
	"lastfm_toptags" => "TOP TAGS:",
	"lastfm_addtags" => "AJOUT DE TAGS",
	"lastfm_addtagslabel" => "Ajout de tags, séparés par des virgules",
	"button_add" => "AJOUT",
	"lastfm_yourtags" => "VOS TAGS:",
	"lastfm_simar" => "Artistes Proches",
	"lastfm_removetag" => "Supprimer Le Tag",
	"lastfm_releasedate" => "Date De Sortie",
	"lastfm_viewtrack" => "Voir la pise sur Last.FM",
	"lastfm_tagerror" => "Echec de modification des tags",
	"lastfm_loved" => "Aimé",
	"lastfm_lovethis" => "J'Aime Cette Piste",
	"lastfm_unlove" => "Je N'Aime Plus Cette Piste",
	"lastfm_notfound" => "%s Non Trouvé",

	// Lyrics info panel
	"button_lyrics" => "Informations (Paroles)",
	"lyrics_lyrics" => "Paroles",
	"lyrics_nonefound" => "Pas De Paroles Trouvées",
	"lyrics_info" => "Pour utiliser le visualiseur de paroles, vous devez utiliser Mopidy avec un serveur Beets et vous assurer que vos fichiers sont taggués avec des paroles",

	// For Discogs/Musicbrainz release tables. LABEL in this context means record label
	// These are all section headers and so should all be UPPER CASE, unless there's a good linguistic
	// reason not to do that
	"title_year" => "ANNEE",
	"title_title" => "TITRE",
	"title_type" => "TYPE",
	"title_label" => "LABEL",
	"label_pages" => "PAGES",

	// For discogs/musicbrains album info. discogs_companies means the companies involved in producing the album
	// These are all section headers and so should all be UPPER CASE, unless there's a good linguistic
	// reason not to do that
	"discogs_companies" => "ENTREPRISES",
	"discogs_personnel" => "PERSONNEL",
	"discogs_videos" => "VIDEOS",
	"discogs_styles" => "STYLES",
	"discogs_genres" => "GENRES",
	"discogs_tracklisting" => "LISTE DES PISTES",
	"discogs_realname" => "NOM REEL:",
	"discogs_aliases" => "ALIAS:",
	"discogs_alsoknown" => "AUSSI CONNU COMME:",
	"discogs_external" => "LIES EXTERNES",
	"discogs_bandmembers" => "MEMBRES DU GROUPE",
	"discogs_memberof" => "MEMBRE DE",
	"discogs_discography" => "DISCOGRAPHIE DE",

	// Discogs info panel
	"button_discogs" => "Informations (Discogs)",
	"discogs_error" => "Il y a eu une erreur réseau ou Discogs a refusé de répondre",
	"discogs_nonsense" => "Aucune information pertinente de Discogs",
	"discogs_noalbum" => "Album non trouvé sur Discogs",
	"discogs_notrack" => "Piste non trouvée sur Discogs",

	// Musicbrainz info panel
	"button_musicbrainz" => "Informations (Musicbrainz)",
	"musicbrainz_error" => "Pas de réponse de MusicBrainz",
	"musicbrainz_contacterror" => "Il y a eu une erreur de communication avec Musicbrainz",
	"musicbrainz_noartist" => "Artiste non trouvé sur Musicbrainz",
	"musicbrainz_noalbum" => "Album non trouvé sur Musicbrainz",
	"musicbrainz_notrack" => "Piste non trouvée sur Musicbrainz",
	"musicbrainz_noinfo" => "Pas d'information trouvée sur Musicbrainz",
	// This is used for date ranges -  eg 2005 - Present
	"musicbrainz_now" => "Aujourd'hui",
	"musicbrainz_origin" => "ORIGINE",
	"musicbrainz_active" => "ACTIF",
	"musicbrainz_rating" => "NOTATION",
	"musicbrainz_notes" => "NOTES",
	"musicbrainz_tags" => "TAGS",
	"musicbrainz_externaldiscography" => "Discographie (%s)",
	"musicbrainz_officalhomepage" => "Page Officielle (%s)",
	"musicbrainz_fansite" => "Site De Fans (%s)",
	"musicbrainz_lyrics" => "Paroles (%s)",
	"musicbrainz_social" => "Réseau Social",
	"musicbrainz_microblog" => "Microblog",
	"musicbrainz_review" => "Critiques (%s)",
	"musicbrainz_novotes" => "(Aucun Vote)",
	// eg: 3/5 from 15 votes
	"musicbrainz_votes" => "%s/5 sur %s votes",
	"musicbrainz_appears" => "CETTE PISTE APPARAÎT SUR",
	"musicbrainz_credits" => "CREDITS",
	"musicbrainz_status" => "STATUS",
	"musicbrainz_date" => "DATE",
	"musicbrainz_country" => "PAYS",
	"musicbrainz_disc" => "DISQUE",

	// SoundCloud info panel
	"button_soundcloud" => "Informations (SoundCloud)",
	"soundcloud_trackinfo" => "Informations Sur La Piste",
	"soundcloud_plays" => "Lectures",
	"soundcloud_downloads" => "Téléchargements",
	"soundcloud_faves" => "Favoris",
	// State means eg State: Finished or State: Unfinished
	"soundcloud_state" => "Etat",
	"soundcloud_license" => "License",
	"soundcloud_buy" => "Acheter La Piste",
	"soundcloud_view" => "Voir sur SoundCloud",
	"soundcloud_user" => "Utilisateur SoundCloud",
	"soundcloud_fullname" => "Nom Complet",
	"soundcloud_Country" => "Pays",
	"soundcloud_city" => "Ville",
	"soundcloud_website" => "Visiter Le Site Web",
	"soundcloud_not" => "Ce panneau ne montre que les informations sur la musique venant de SoundCloud",

	// Wikipedia Info Panel
	"button_wikipedia" => "Informations (Wikipedia)",
	"wiki_nothing" => "Rien n'a été trouvé sur Wikipedia",
	"wiki_fail" => "Wikipedia n'a rien pu trouver sur '%s'",
	"wiki_suggest" => "Wikipedia n'a trouvé aucune page correspondant à '%s'",
	"wiki_suggest2" => "Voici quelques suggestions qui ont été indiquées",

	// Keybindings editor
	"title_keybindings" => "Raccourcis Clavier",
	"button_volup" => "Monter Volume",
	"button_voldown" => "Baisser Volume",

	// Extras for mobile version
	"button_playlist" => "Playlist",
	"button_playman" => "Gestion De La Playlist",
	"button_mob_history" => "Historique Du Panneau d'Information",
	"label_streamradio" => "Radios Nationales et Locales"

);

?>
