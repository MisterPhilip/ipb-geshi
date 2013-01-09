ipb-geshi
=========

GeSHi Drop-in replacement for IPB 3.4.x

## Installation

Installation of the [GeSHi](http://qbnz.com/highlighter/) code is fairly straightforward:
 
 1. Upload the files from the uploads directory, similar to how you would for an IPB install / upgrade. If you've changed
     your default admin folder name, you should do the same here.
 1. Within the `/installables` you'll find a few different XML files that you'll need to upload within your admin interface:
    1. `bbcode_GeSHi.xml` is a BB Code replacement for the code bbcode.
    You should delete that bbcode (if IN_DEV) and install this one. Alternatively, you can change the
    following items in the default code bbcode:
        * "Custom BBCode Tag": codesyntax
        * "Custom BBCode Aliases": code,codebox
        * "OR PHP file to execute": codesyntax.php
    1. `hook_GeSHi.xml` is a hook that replaces the prettify code with the GeSHi code.
    This also adds in the settings within the admin interface.  You should install this like you would for any other hook
    (`Applications & Modules  >  Manage Hooks > Install Hook`)
 1. At this point, all new posts will be using GeSHi. If you have existing posts with code,
    you'll likely want to drop the cache for that BBCode (`Post Content > BBCode Management > "Code" > "Drop all cached items"`)

## Options

A few settings are also included with this hook, which you could find under `System Settings > System > Code Highlighter`.
Although there are descriptions for each, here are all of the following settings and what they do:

 * **Parse outdated bbcodes?**: Old BBCodes ([html], [php], [sql], [xml]) no longer work with the [code] tag. If this is enabled
 it attempts to parse these into the correct language if your users enter in these bbcodes. Note, this is currently does
 not work waiting on 3rd party fix. (Default: no)
 * **Key to hightlight a line**: If this is a non-empty string, then any line prefixed with this value will be highlighted
 by GeSHi. (Default: `##`)
 * **Default language**: If a user's language does not exist, or they did not provide one, you should select which one
 to use here. For example, if your users mainly use JavaScript, you should select JavaScript. (Default: HTML5)
 * **Enable URLs to functions**: GeSHi will generate links to the languages documentation for functions or other keywords.
 This can be extremely useful for your users, and is recommended to leave on. (Default: yes)
 * **Use CSS file instead of inline**: GeSHi by default will use inline styles on the generated HTML. This can generate
 extra bloat if your visitors view a lot of code. However, the trade off is that the CSS files can become quite large if
 you generate it for a lot of languages. For example, if you were to leave all of the languages in and generate a CSS file,
 you'd end up with a 100kb CSS file. It's likely that your visitors would rarely use a lot of the languages, but I've
 included them for your convenience. (Default: no)
 * **Hide line numbers if no starting number**: If there are no line numbers, or if the starting line number is "0" and this is
 enabled, the line numbers will be completely disabled. By default this is disabled (Default: no).
 * **Fancy line numbers**: If you wish to have every nth line number be bold, you should enter the nth line number here.
 Otherwise, you can change this to 0 to disable this feature (Default: 0).
 * **Tab size**: This should be the number of spaces is equal to one tab. (Default: 4)
 * **Smart Tabs**: This enables smart tabs (I still have yet to figure out what these do.) Default is disabled (No).
 * **Auto-Caps**: [Auto-caps](http://qbnz.com/highlighter/geshi-doc.html#auto-caps-nocaps) is a feature in GeSHi that
 will upper/lowercase user's code based on the language's lexics. (Default: Don't change)

## Styling

GeSHi doesn't provide many options for styling right off the bat. However, the CSS stylings that I've added can be
located within the admin interface and you can edit it there (Look & Feel > Manage Theme > CSS > `GeSHi`). If you decide
to enable CSS classes, you should update this file to include all of the classes for all of the languages you include.

I've included a tool to do build a CSS file for all of the languages within your language folder. It is located at
`_tools/cssgen.php`, and it should be placed within `/admin/sources/classes/text/parser/bbcode/sources/geshi/`. You can
then call PHP to run the file and generate a geshi.css file:
```
$: php cssgen.php
```
Once the file is generated, copy its contents into the one within your admin interface. It is highly recommended that if
you decide to generate a CSS file that you delete languages nobody on your forums will use. Otherwise you'll end up with
a 100kb CSS file.

## Other Notes

 * GeSHi cannot automatically detect the language, unlike prettify.
 * By default, if the language cannot be found and the default cannot load, it will return `<pre>{content}</pre>` safely.
