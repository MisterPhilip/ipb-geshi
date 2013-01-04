<?php
/**
 * GeSHi drop-in replacement highlighter for IP.Board v3.4.1
 *
 * @author 		Philip Lawrence
 * @copyright	(c) 2012 - 2013 Philip Lawrence
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://misterphilip.com
 * @version     10000
 *
 */

// Load in the current plugin class
if( !class_exists('bbcode_plugin_code') )
{
	require_once( IPS_ROOT_PATH . 'sources/classes/text/parser/bbcode/defaults.php' );/*noLibHook*/
}

// Load in GeSHi sources
if( !class_exists('GeSHi') )
{
	require_once( IPS_ROOT_PATH . 'sources/classes/text/parser/bbcode/sources/geshi/geshi.php' );/*noLibHook*/
}

class bbcode_plugin_geshi extends bbcode_plugin_code
{
    /**
     * Default Language
     *
     * Populate this with the default language (if they choose auto or select a language not in the allowed langauges array)
     *
     * @var string
     * @protected
     */
    protected $_defaultLanguage = 'html5';
    
    /**
     * Allowed Languages
     * 
     * Populate this array with GeSHi values you plan on allowing
     * There should be a corresponding file within the "sources/geshi/languages/[language].php" dir
     *
     * @var array
     * @protected
     */
    protected $_allowedLanguages = array( 
        '4cs', '6502acme', '6502kickass', '6502tasm', '68000devpac', 'abap', 'actionscript', 'actionscript3', 
        'ada', 'algol68', 'apache', 'applescript', 'apt_sources', 'arm', 'asm', 'asp', 'asymptote', 'autoconf', 
        'autohotkey', 'autoit', 'avisynth', 'awk', 'bascomavr', 'bash', 'basic4gl', 'bf', 'bibtex', 'blitzbasic', 
        'bnf', 'boo', 'c', 'c_loadrunner', 'c_mac', 'caddcl', 'cadlisp', 'cfdg', 'cfm', 'chaiscript', 'cil', 
        'clojure', 'cmake', 'cobol', 'coffeescript', 'cpp-qt', 'cpp', 'csharp', 'css', 'cuesheet', 'd', 'dcl', 
        'dcpu16', 'dcs', 'delphi', 'diff', 'div', 'dos', 'dot', 'e', 'ecmascript', 'eiffel', 'email', 'epc', 
        'erlang', 'euphoria', 'f1', 'falcon', 'fo', 'fortran', 'freebasic', 'freeswitch', 'fsharp', 'gambas', 
        'gdb', 'genero', 'genie', 'gettext', 'glsl', 'gml', 'gnuplot', 'go', 'groovy', 'gwbasic', 'haskell', 
        'haxe', 'hicest', 'hq9plus', 'html4strict', 'html5', 'icon', 'idl', 'ini', 'inno', 'intercal', 'io', 
        'j', 'java', 'java5', 'javascript', 'jquery', 'kixtart', 'klonec', 'klonecpp', 'latex', 'lb', 'ldif', 
        'lisp', 'llvm', 'locobasic', 'logtalk', 'lolcode', 'lotusformulas', 'lotusscript', 'lscript', 'lsl2', 
        'lua', 'm68k', 'magiksf', 'make', 'mapbasic', 'matlab', 'mirc', 'mmix', 'modula2', 'modula3', 'mpasm', 
        'mxml', 'mysql', 'nagios', 'netrexx', 'newlisp', 'nsis', 'oberon2', 'objc', 'objeck', 'ocaml-brief', 
        'ocaml', 'octave', 'oobas', 'oorexx', 'oracle11', 'oracle8', 'oxygene', 'oz', 'parasail', 'parigp', 
        'pascal', 'pcre', 'per', 'perl', 'perl6', 'pf', 'php-brief', 'php', 'pic16', 'pike', 'pixelbender', 
        'pli', 'plsql', 'postgresql', 'povray', 'powerbuilder', 'powershell', 'proftpd', 'progress', 'prolog', 
        'properties', 'providex', 'purebasic', 'pycon', 'pys60', 'python', 'q', 'qbasic', 'rails', 'rebol', 'reg', 
        'rexx', 'robots', 'rpmspec', 'rsplus', 'ruby', 'sas', 'scala', 'scheme', 'scilab', 'sdlbasic', 'smalltalk', 
        'smarty', 'spark', 'sparql', 'sql', 'stonescript', 'systemverilog', 'tcl', 'teraterm', 'text', 'thinbasic', 
        'tsql', 'typoscript', 'unicon', 'upc', 'urbi', 'uscript', 'vala', 'vb', 'vbnet', 'vedit', 'verilog', 
        'vhdl', 'vim', 'visualfoxpro', 'visualprolog', 'whitespace', 'whois', 'winbatch', 'xbasic', 'xml', 
        'xorg_conf', 'xpp', 'yaml', 'z80', 'zxbasic',
    );
    
    /**
     * Fancy line numbers
     * 
     * If you'd like to use fancy numbers, specify the nth line number
     * Otherwise, use false to disable fancy line numbers
     *
     * @var mixed
     * @protected
     */
    protected $_fancyLineNumber = false;
    
    /**
     * Use CSS classes 
     * 
     * If you populate this as true, GeSHi will use CSS classes instead of inline code
     *
     * @var bool
     * @protected
     */
    protected $_useCssClasses = false;
    
    /**
     * Additional CSS Class (wrapper)
     * 
     * If you populate this with a non-empty string, it will output these classes on the <pre> tag.
     * The language is automatically included within the class.
     *
     * @var string
     * @protected
     */
    protected $_overallCssClass = 'foobar';
    
    
	/**
	 * {@inherit}
	 */
	public function __construct( ipsRegistry $registry, $_parent='' )
	{
		$this->currentBbcode	= 'geshi';
        $this->_parent = $_parent;
		
		parent::__construct( $registry, $_parent );
	}
	
	/**
	 * Do the actual replacement
	 *
	 * @access	protected
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	string				BBCode content, ready for editing
	 */
	protected function _replaceText( $txt )
	{
        // Check for the correct class, less resource intensive than regex
        if( stristr( $txt, '_prettyXPrint' ) )
        {
            // We already have the HTML for it, let's clean it up and GeSHi it
            if( preg_match_all( '#<pre[^>]*class\s?=\s?(["\'])((?:(?!\1).)*)_prettyXprint((?:(?!\1).)*)\1[^>]*>((?:(?!</pre>).)*)</pre>#is', $txt, $matches ) > 0 )
            {
                $codeboxCount = count( $matches[0] );
                
                // Loop through all of our matches
                for($i = 0; $i < $codeboxCount; $i++)
                {
                    $options = array();
                    
                    // Grab the other classes (options in this case) 
                    list($options['lang'], $options['lineNum'] ) = explode(' ', trim( $matches[2][$i] . $matches[3][$i] ) );
                    $options['lang'] = str_replace( '_lang-', '', $options['lang'] );
                    $options['lineNum'] = str_replace( '_linenums:', '', $options['lineNum'] );
                    
                    // Replace the current with the new
                    $replacement = $this->_colorfy( $matches[4][$i], $options );
                    $txt = str_replace( $matches[0][$i] , $replacement, $txt );
                }
            }
        }
        
        // Run the existing for bbcode versions
		parent::_replaceText( $txt );
        
        // Return the awesome looking code
		return $txt;
	}
    
	/**
	 * {@inherit}
	 */
	protected function _buildOutput( $content, $option )
	{
        // This is the original, used for BBCode.. which could cause issues with HTML (e.g. <br> in source)
        
		$content        = trim( $content );
		
		$content = preg_replace( '#(<br(?:[^>]+?)?>)#i', '', $content );
		$content = trim( $content );
		
		$content = str_replace( '<!-preserve.newline-->', "\n", $content );
        
        // Grab the option
        $lineNums = 1;
		$langAdd  = '';
		
		if ( !is_array( $option ) )
		{
            list( $option['lang'], $option['lineNum']) = explode( ':', $option );
		}
		
        // Make it pretty!
        return $this->__colorfy( $content, $option );
	}
    

	/**
	 * GeSHi the code
	 *
	 * @access	protected
	 * @param	string      $content   Content to prettify
	 * @param	array       $options   [Optional] code options
	 * @return	string			       GeSHi'd content
	 */
	protected function _colorfy( $content, array $options )
	{
        // Revert some of IPB's doings
        $content = html_entity_decode( $content );
        
        // Convert the languages from the current editor
        switch( $options['lang'] )
        {
            case 'js': 
                $options['lang'] = 'javascript';
            break;
            case 'html':
                $options['lang'] = 'html5';
            break;
            case 'auto':
                $this->_defaultLanguage;
            break;
            default:
                // Nothing to do here, we'll check in just a second on these
            break;
        }
        
        // Verify we have the language in the array
        if( ! in_array( $options['lang'], $this->_allowedLanguages ) )
        {
            $options['lang'] = $this->_defaultLanguage;
        }
        
        // Load up GeSHi
        $geshi = new GeSHi( $content , $options['lang'] );
        
        // Parse any settings
        $geshi->set_header_type( GESHI_HEADER_PRE );
        if( $this->_useCssClasses )
        {
            $geshi->enable_classes();
        }
        
        $this->_overallCssClass .= ' geshi';
        $geshi->set_overall_class( $this->_overallCssClass );
        
        // Line numbers
        $lineNums = intval( $options['lineNum'] );
        if( $lineNums > 0 )
        {
            // Check to see if we need to use "fancy" numbering
            if( $this->_fancyLineNumber !== false )
            {
                $geshi->enable_line_numbers( GESHI_FANCY_LINE_NUMBERS, intval( $this->_fancyLineNumber ) );
            }
            else
            {
                $geshi->enable_line_numbers( GESHI_NORMAL_LINE_NUMBERS );
            }
            $geshi->start_line_numbers_at( $lineNums );
        }
        else
        {
            $geshi->enable_line_numbers( GESHI_NO_LINE_NUMBERS );
        }
        
        // Return our parsed code
		return $geshi->parse_code();
	}

}