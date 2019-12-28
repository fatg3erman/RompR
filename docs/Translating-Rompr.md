# How To Translate RompR

You don't need to know how to code. All you need is the ability to use a text editor and follow some simple instructions. Oh, and you need to speak two languages, that's quite important :) Please don't use Google Translate or any other online service. If I thought they were any good, I'd use them myself.

## Using GitHub

If you're going to do this by forking RompR on github so you can easily keep track of things, please base all your changes on the develop branch. The master branch is for creating releases only and will be merged with develop prior to a release.

## How To Do It

First thing, get the most up-to-date copy of the English translation from

    rompr/international/en.php

Make a copy of this file. The name you give it should reflect the language you are translating into. RompЯ will use the file name to help it automatically select an appropriate language. The file name should be the two letter code for your language. There is a list of those codes [here](http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes) under the column 639-1.

For example, if you are translating into French, call it 'fr.php'. For German, 'de.php'. For Russian, 'ru.php'

If you can't find a two letter code for your language, don't worry. Just give the file any name ending in .php - it will still be available as an option in a drop-down menu in the Configuration menu in RompЯ.

The start of the English file, which is called 'en.php', looks like this:

    <?php

    // The first term here is the name that will appear in the drop-down list
    // This has the form $langname['file_name without .php extension'] = "Display Name";
    // Try to name your file as the two-letter language code so RompR can pick a suitable
    // default language automatically.

    $langname['en'] = "English";

    $languages['en'] = array (

    // The Sources Chooser Button tooltips
    "button_local_music" => "Local Music",
    "button_file_browser" => "File Browser",
    "button_lastfm" => "Last.FM Radio",
    "button_internet_radio" => "Internet Radio Stations and Podcasts",
    "button_albumart" => "Album Art Manager",

I know it's code. but if you don't code, don't be scared. Here's an example.
For the example, I'll say we're translating into French, because I do know a few words of French. (Please, French speakers, don't laugh at my poor efforts, I'm trying to help people :) )

The two-letter country code for French is 'fr' so I'd name my file 'fr.php' and then the edit would look something like this:

    <?php

    // The first term here is the name that will appear in the drop-down list
    // This has the form $langname['file_name without .php extension'] = "Display Name";
    // Try to name your file as the two-letter language code so RompR can pick a suitable
    // default language automatically.

    $langname['fr'] = "Français";

    $languages['fr'] = array (

    // The Sources Chooser Button tooltips
    "button_local_music" => "Musique Locale",
    "button_file_browser" => "Fichier",

Note what I've edited. I've changed 'en' in two places to 'fr'. I've changed 'English' to 'Français' - this is what will appear in the drop-down list in the configuration menu. Then it's just a case of translating all the english text on the right-hand side of the => symbols. Make sure you don't remove quotation marks or commas. Also, strings must not contain " \ or /.

Please send your copies to me so I can include them in future releases. You will get a credit for your trouble :)

If you don't speak English you can always translate from another language you do speak, if it exists. But if you don't speak English you won't be reading this, so...
