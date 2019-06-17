<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Plyr Plyr player integration
 * @ingroup     UnaModules
 * 
 * @{
 */

/**
 * Plyr player integration.
 * @see BxDolPlayer
 */
class BxPlyrPlayer extends BxDolPlayer
{
    /**
     * Standard view initialization params
     */
    protected static $CONF_STANDARD = '
    <div class="bx_player_plyr" {attrs_wrapper}>
        <video {attrs}>
            {webm}
            {mp4}
            {captions}
        </video>
    </div>
    ';

    /**
     * Minimal view initialization params
     */
    protected static $CONF_MINI = "";

    /**
     * Embed view initialization params
     */
    protected static $CONF_EMBED = "";


    protected $_oTemplate;
    protected $_bJsCssAdded = false;

    public function __construct ($aObject, $oTemplate)
    {
        parent::__construct ($aObject);

        if ($oTemplate)
            $this->_oTemplate = $oTemplate;
        else
            $this->_oTemplate = BxDolTemplate::getInstance();

        $this->_aSkins = array(
        );
    }

    public function getCodeAudio ($iViewMode, $aParams, $bDynamicMode = false)
    {
        // TODO:
    }
    
    public function getCodeVideo ($iViewMode, $aParams, $bDynamicMode = false)
    {
        // set visual mode

        switch ($iViewMode) {
        case BX_PLAYER_STANDARD:
        case BX_PLAYER_MINI:
        case BX_PLAYER_EMBED:
        default:
                $sInit = self::$CONF_STANDARD;
        }

        // attrs

        $sId = 'BxPlyr' . mt_rand();
        $sAttrsWrapper = '';
        $aAttrsDefault = array(
            'id' => $sId,
            'controls' => '',
            'controlsList' => 'nodownload',
            'preload' => 'none',
            'autobuffer' => '', 
        );
        $aAttrs = isset($aParams['attrs']) && is_array($aParams['attrs']) ? $aParams['attrs'] : array();
        $aAttrs = array_merge($aAttrsDefault, $aAttrs);
        if (isset($aParams['poster']) && is_string($aParams['poster']))
            $aAttrs['poster'] = $aParams['poster'];

        if (isset($aParams['styles']) && is_string($aParams['styles']) ? $aParams['styles'] : false) {
            $sAttrsWrapper = bx_convert_array2attrs(array('styles' => $aParams['styles']));
            unset($aParams['styles']);
        }
            
        $sAttrs = bx_convert_array2attrs($aAttrs);

        // generate files list for HTML5 player
        
        $aTypes = array(
            'webm' => '<source type="video/webm" src="{url}" size="{size}" />',
            'mp4' => '<source type="video/mp4" src="{url}" size="{size}" />',
        );              
        $mp4 = '';
        $webm = '';
        foreach ($aTypes as $s => $ss) {
            if (!isset($aParams[$s]))
                continue;
            if (is_array($aParams[$s])) {
                foreach ($aParams[$s] as $sType => $sUrl) {
                    if (empty($sUrl) || !isset($this->_aSizes[$sType]))
                        continue;
                    $$s .= str_replace(
                        array('{url}', '{size}'), 
                        array($sUrl, $this->_aSizes[$sType]), 
                        $ss
                    );
                }
            }
            elseif (is_string($aParams[$s]) && !empty($aParams[$s]))
                $$s = str_replace('{url}', $aParams[$s], $ss);
            else
                $$s = '';
        }

        // player code

        $sCode = $this->_replaceMarkers($sInit, array(
            'attrs' => $sAttrs,
            'attrs_wrapper' => $sAttrsWrapper,
            'webm' => $webm,
            'mp4' => $mp4,
            'captions' => isset($aParams['captions']) ? $aParams['captions'] : '',
        ));

        // plyr initialization
        $sFormat = getParam('sys_player_default_format');
        $aOptions = array_merge(array(
            // 'debug' => true,
            'quality' => array('default' => $this->_aSizes[$sFormat]),
            'displayDuration' => false,
        ), $this->_aConfCustom);
        $sInitEditor = "
            gl$sId = new Plyr('#$sId', " . json_encode($aOptions) . ");
        ";

        // load necessary JS and CSS

        if ($bDynamicMode) {

            list($aJs, $aCss) = $this->_getJsCss(true);
            
            $sCss = $this->_oTemplate->addCss($aCss, true);
            
            $sScript = $sCss . "<script>
                if ('undefined' == typeof(window.Plyr)) {
                    bx_get_scripts(" . json_encode($aJs) . ", function () {
                        $sInitEditor
                    });
                } else {
                	setTimeout(function () {
                    	$sInitEditor
                    }, 10); // wait while html is rendered in case of dynamic adding html
                }
            </script>";

        } else {            
                
            $sScript = "
            <script>
                $(document).ready(function () {
                    $sInitEditor
                });
            </script>";

        }

        return $this->_addJsCss($bDynamicMode) . $sScript . $sCode;
    }

    /**
     * Add css/js files which are needed for editor display and functionality.
     */
    protected function _addJsCss($bDynamicMode = false, $sInitEditor = '')
    {
        if ($bDynamicMode)
            return '';
        if ($this->_bJsCssAdded)
            return '';

        list($aJs, $aCss) = $this->_getJsCss();

        $this->_oTemplate->addJs($aJs);
        $this->_oTemplate->addCss($aCss);
        $this->_bJsCssAdded = true;
        return '';
    }

    protected function _getJsCss($bUseUrlsForJs = false)
    {
        $sJsPrefix = $bUseUrlsForJs ? BX_DOL_URL_MODULES : BX_DIRECTORY_PATH_MODULES;
        $sJsSuffix = $bUseUrlsForJs ? '' : '|';
        
        $aJs = array(
            $sJsPrefix . 'boonex/plyr/plugins/plyr/' . $sJsSuffix . 'plyr.min.js',
        );
        
        $aCss = array(
            BX_DIRECTORY_PATH_MODULES . 'boonex/plyr/plugins/plyr/|plyr.css',
            BX_DIRECTORY_PATH_MODULES . 'boonex/plyr/template/css/|main.css',
        );
        
        return array($aJs, $aCss);
    }
}

/** @} */
