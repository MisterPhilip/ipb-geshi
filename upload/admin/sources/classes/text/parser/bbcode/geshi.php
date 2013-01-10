<?php
/**
 * GeSHi drop-in replacement for IP.Board v3.4.1
 *
 * @author      Philip Lawrence
 * @copyright   (c) 2012 - 2013 Philip Lawrence
 * @license     https://github.com/MisterPhilip/ipb-geshi/blob/master/LICENSE
 * @package     IP.Board
 * @link        http://misterphilip.com/ipb.php?action=view&product=GeSHi
 * @link        https://github.com/MisterPhilip/ipb-geshi/
 * @version     10001
 *
 */

// Load in the current plugin class
if( !class_exists('bbcode_plugin_code') )
{
	require_once( IPS_ROOT_PATH . 'sources/classes/text/parser/bbcode/defaults.php' );/*noLibHook*/
}

// Load in GeSHi class
if( !class_exists('GeSHi') )
{
	require_once( IPS_ROOT_PATH . 'sources/classes/text/parser/bbcode/sources/geshi/geshi.php' );/*noLibHook*/
}

class bbcode_plugin_geshi extends bbcode_plugin_code
{

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
        
        if( $this->settings['geshi_parseOld'] )
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
                
                $lang = 'php';
                $line = 0;
                
                if( preg_match( '#_lang-(\w+)#i', $openTag, $matches ) )
                    $lang = $matches[1];
                    
                if( preg_match( '#_linenums:(\d+)#i', $openTag, $matches ) )
                    $line = $matches[1];

                $content = substr( $txt, $tags['open'][ $id ], ( $tags['close'][ $id ] - $tags['open'][ $id ] ) );

                $content = $this->_colorfy( $content, array( 'lang' => $lang, 'lineNum' => $line ) );
                
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
        // Grab the languages ------------------------------------------------------------------------------------------
        // Check to see if we can load the default language or not
        $language = $this->settings['geshi_defaultLanguage'];

        if( $this->_isLanguageAvailable( $language ) === false )
        {
            if( $this->_isLanguageAvailable( 'html5' ) === false )
            {
                return $content;
            }

            // phew, we still have a default.
            $language = 'html5';
        }

        // Convert the languages from the current editor
        switch( $options['lang'] )
        {
            case 'js': 
                $options['lang'] = 'javascript';
            break;
            case 'html':
                // Default to HTML5, unless specified otherwise
                $options['lang'] = 'html5';
            break;
            case 'auto':
            break;
            default:
                // Nothing to do here, we'll check in just a second on these
            break;
        }

        if( $this->_isLanguageAvailable( $options['lang'] ) !== false )
        {
            // We have this language available, let's use it.
            $language = $this->_isLanguageAvailable( $options['lang'] );
        }

        // Start the content -------------------------------------------------------------------------------------------
        $content = html_entity_decode( $content );
        $geshi = new GeSHi( $content , $language );
        
        // Parse any settings ------------------------------------------------------------------------------------------
        // -> Basics
        $geshi->set_header_type( GESHI_HEADER_PRE );
        $geshi->set_overall_class( 'geshi_block' );
        $geshi->enable_strict_mode( false );
        $geshi->set_overall_style( '' );
        $geshi->set_code_style( '' );

        // -> URLs on Functions?
        if( $this->settings['geshi_clickableURL'] )
        {
            $geshi->enable_keyword_links( false );
        }

        // -> External stylesheet?
        if( $this->settings['geshi_externalCss']  )
        {
            $geshi->enable_classes();
        }

        // -> Tab Size
        if( $this->settings['geshi_tabSize'] > 0 )
        {
            $geshi->set_tab_width( $this->settings['geshi_tabSize'] );
        }

        // -> AuTo-CaPs
        if( $this->settings['geshi_autoCaps'] == 1 )
        {
            $geshi->set_case_keywords( GESHI_CAPS_UPPER );
        }
        else if( $this->settings['geshi_autoCaps'] == 2 )
        {
            $geshi->set_case_keywords( GESHI_CAPS_LOWER );
        }

        // -> Line numbers
        $startingLine = intval( $options['lineNum'] );
        if( ! $this->settings['geshi_gutter'] || $startingLine > 0 )
        {
            // The admin wants the gutter to be displayed at all times, starting line number will be 1 if it doesn't exist
            if( $startingLine == 0 )
            {
                $startingLine = 1;
            }

            // Check to see if we need to use "fancy" numbering
            if( $this->settings['geshi_fancyLines'] != 0 )
            {
                $geshi->set_line_style( '' ); // Remove unnecessary markup
                $geshi->enable_line_numbers( GESHI_FANCY_LINE_NUMBERS, $this->settings['geshi_fancyLines'] );
            }
            else
            {
                $geshi->set_line_style( '', 'font-weight: bold' );
                $geshi->set_code_style( 'font-weight: normal' );
                $geshi->enable_line_numbers( GESHI_NORMAL_LINE_NUMBERS );
            }
            $geshi->start_line_numbers_at( $startingLine );
        }
        else
        {
            $geshi->enable_line_numbers( GESHI_NO_LINE_NUMBERS );
        }

        // -> Finally, highlight anything?
        if( $this->settings['geshi_highlightKey'] != '' && strpos( $content, $this->settings['geshi_highlightKey'] ) !== false )
        {
            $highlightLines = array();
            $currentLine = ( $startingLine == 0 ) ? 1 : $startingLine;
            $lengthOfKey = strlen( $this->settings['geshi_highlightKey'] );

            // @TODO: Clean this up, find a way to use less memory on large posts
            $contentArray = explode( PHP_EOL, $content );
            $content = '';
            foreach( $contentArray as $line )
            {
                if( strpos( $line, $this->settings['geshi_highlightKey'] ) === 0 )
                {
                    $highlightLines[ ] = $currentLine;
                    $line = substr( $line, $lengthOfKey );  // Remove the highlight key from the beginning
                }
                $content.= $line . PHP_EOL;
                $currentLine++;
            }

            if( count( $highlightLines ) > 0 )
            {
                $geshi->highlight_lines_extra( $highlightLines );
            }
        }

        // Finish him! ( http://youtu.be/_hHDxlm66dE )------------------------------------------------------------------
        $content = $geshi->parse_code();

        // Force IPB to NOT parse URLs
        $content = preg_replace( '#(https|http|ftp)://#' , '\1&#58;//', $content );

        // Return our parsed code
		return $content;
	}

    /**
     * Check to make sure the language file exists
     *
     * @param $language
     * @return bool|mixed
     */
    protected function _isLanguageAvailable( $language )
    {
        // Clean up the language name in case of naughtiness
        $language = strtolower( preg_replace( "/[^a-zA-Z0-9]+/", "", $language ) );
        if( ! is_file( IPS_ROOT_PATH . 'sources/classes/text/parser/bbcode/sources/geshi/languages/' . $language . '.php' ) )
        {
            return false;
        }
        return $language;
    }

}