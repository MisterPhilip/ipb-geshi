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
    public function __construct( ipsRegistry $registry, $_parent=NULL )
    {
        $this->currentBbcode    = 'geshi';
        $this->_parent          = $_parent;
        
        // Do what is normally done in the parent __construct
        // We can't call parent::__construct since bbcode_plugin_code would overwrite the currentBbcode
        $this->registry        =  $registry;
        $this->DB              =  $this->registry->DB();
        $this->settings        =& $this->registry->fetchSettings();
        $this->request         =& $this->registry->fetchRequest();
        $this->lang            =  $this->registry->getClass('class_localization');
        $this->member          =  $this->registry->member();
        $this->memberData      =& $this->registry->member()->fetchMemberData();
        $this->cache           =  $this->registry->cache();
        $this->caches          =& $this->registry->cache()->fetchCaches();
        
        $this->_parentBBcode = $_parent;
        
        /* Retrieve bbcode data */
        $bbcodeCache    = $this->cache->getCache('bbcode');
        $this->_bbcode  = $bbcodeCache[ $this->currentBbcode ];
    }
    
    /**
     * Do the actual replacement
     *
     * @access    protected
     * @param     string        $txt    Parsed text from database to be edited
     * @return    string                BBCode content, ready for editing
     */
    protected function _replaceText( $txt )
    {
        // Convert old tag (and outdated tags) to the new code tag
        $oldTags = array( );
        
        if( $this->settings['codesyntax_parseOld'] )
            $oldTags = array_merge( $oldTags, array( 'html', 'php', 'sql', 'xml' ) );
        
        foreach( $this->_retrieveTags() as $tag)
        {
            if( in_array( $tag, $oldTags ) )
            {
                $lang = $tag;
            }
            else
            {
                $lang = 'auto';
            }
            
            if ( stristr( $txt, '[' . $tag . ']' ) )
            {
                $txt = str_ireplace( '[' . $tag . ']', '[code=' . $lang . ':0]', $txt );
            } 
                
            if ( $tag != 'code' && stristr( $txt, '[/' . $tag . ']' ) )
            {
                $txt = str_ireplace( '[/' . $tag . ']', '[/code]', $txt );
            }
        }
        // Check for the correct class, less resource intensive than regex
        if( stristr( $txt, '_prettyXPrint' ) )
        {
            $tags = $this->_parent->getTagPositions( $txt, 'pre', array( '<' , '>' ) );
            
            foreach( $tags['open'] as $id => $val )
            {
                $tagEnd = strpos( $txt, '>', $tags['openWithTag'][ $id ] );
                $openTag = substr( $txt, $tags['openWithTag'][ $id ], ( $tagEnd - $tags['openWithTag'][ $id ] + 1) );
                
                $origLength = $tags['closeWithTag'][ $id ] - $tags['openWithTag'][ $id ];
                
                
                $lang = 'php';
                $line = 0;
                
                if( preg_match( '#_lang-(\w+)#i', $openTag, $matches ) )
                    $lang = $matches[1];
                    
                if( preg_match( '#_linenums:(\d+)#i', $openTag, $matches ) )
                    $line = $matches[1];
                
                $content = html_entity_decode( substr( $txt, $tags['open'][ $id ], ( $tags['close'][ $id ] - $tags['open'][ $id ] ) ) );
                $content = $this->_colorfy( $content, array( 'lang' => $lang, 'lineNum' => $line ) );
                
                $newLength = strlen( $content );
                
                $txt = substr_replace( $txt, $content, $tags['openWithTag'][ $id ], ( $tags['closeWithTag'][ $id ] - $tags['openWithTag'][ $id ] ) );
                
                // Update all lengths (we're cheating here!)
                $tags = $this->_parent->getTagPositions( $txt, 'pre', array( '<' , '>' ) );
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
        // This is the original, used for BBCode ([code])
        $content        = trim( $content );
        
        $content = preg_replace( '#(<br(?:[^>]+?)?>)#i', '', $content );
        $content = trim( $content );
        
        $content = str_replace( '<!-preserve.newline-->', "\n", $content );
        
        // Grab the option
        $lineNums = 1;
        $langAdd  = '';
        
        if ( ! is_array( $option ) )
        {
            list( $options['lang'], $options['lineNum']) = explode( ':', $option );
        }
        else
        {
            $options = $option;
        }
        
        // Make it pretty!
        return $this->_colorfy( $content, $options );
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