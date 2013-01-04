ipb-geshi
=========

GeSHi Drop-in replacement for IPB 3.4.x

## Installation

Installation of the GeSHi code is fairly straightforward: 
 
 1. Upload the files from the uploads directory, similar to how you would for an IPB install / upgrade
 1. Within the `/installables` you'll find a few different XML files. 
    1. `bbcode_geshi.xml` is a BB Code replacement for the code bbcode. 
    You should delete that bbcode and install this one. Alternatively, you can change the 
    following items in the default code bbcode:
        * Custom BBCode Tag: geshi
        * Custom BBCode Aliases: code,codebox
        * OR PHP file to execute: geshi.php
    1. `hook_geshi.xml` is a (skin overloading) hook that removes the prettify code and adds in `geshi.css`. 
    You should install this like a normal hook (Applications & Modules  >  Manage Hooks > Install Hook)
 1. At this point, all new posts will be using GeSHi. If you have existing posts with code, 
    you'll likely want to rebuild the content of them (Tools & Settings  >  Recount & Rebuild > Rebuild Content: Post Content)

## Options

There are a few options you can change with how GeSHi is output. These options are not in the interface 
and require some code changes. However, they are still easy to update.

Within `/admin/sources/classes/text/parser/bbcode/geshi.php` you have a few properties:

  * `$_defaultLanguage` - This allows you to set the default (or auto) option. If your board is more 
  likely to use PHP you could set it to 'php': `protected $_defaultLanguage = 'php';`
  
  * `$_allowedLanguages` - This is an array of all of the languages that can be used. Each of these 
  items is a file within `/admin/sources/classes/text/parser/bbcode/sources/geshi/languages`. If you 
  do not need that language, you can simply remove the language file and the array item. By default 
  all languages are included. 
  
  * `$_fancyLineNumber` - GeSHi allows for "fancy" line numbering where every nth line number is bold. 
  You can set this to the nth number to allow for it, or `false` to disable it. For example, if I wanted 
  every 5th line number to be bold: `protected $_fancyLineNumber = 5;`
  
  * `$_useCssClasses` - By default GeSHi's styling is done inline. If you'd prefer GeSHi to use CSS classes 
  instead, you should set this to true. You'll need to generate the classes (see: /_tools/README). It should 
  be noted that you should remove all languages you do not need, otherwise you'll end up with a very bloated 
  CSS file (100kb, compressed). 
  
  * `$_overallCssClass` - If you'd like to add a custom CSS class to include extra styles, you can do that here. 
  By default the name of the langauge (e.g. "html5" or "php") is included, as well as "geshi". 

## Styling

Currently, the styling is the same as IPB's default code box. You can update that by either changing 
`$_overallCssClass` and including your own styles, or by directly editing `/public/style_css/geshi.css`