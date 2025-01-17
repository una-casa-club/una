<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

/**
 * Pages.
 *
 * It allows to display pages which are built from studio.
 *
 * The new system has the following page features:
 * - Layouts: page can have any structure, not just columns.
 * - SEO: page can have own SEO options, like meta tags and meta keywords, as well as instructions for search bots.
 * - Cache: page can be cached on the server.
 * - Access control: page access can be controlled using member levels.
 *
 *
 *
 * @section page_create Creating the Page object:
 *
 * 1. add record to 'sys_objects_page' table:
 *
 * - object: name of the page object, in the format: vendor prefix, underscore, module prefix, underscore, internal identifier or nothing; for example: bx_profiles_view - profile view page.
 * - title: name to display as page title.
 * - module: the module this page belongs to.
 * - layout_id: page layout to use, this is id of the record from 'sys_pages_layouts' table.
 * - visible_for_levels: bit field with set of member level ids. To use member level id in bit field - the level id minus 1 as power of 2 is used, for example:
 *      - user level id = 1 -> 2^(1-1) = 1
 *      - user level id = 2 -> 2^(2-1) = 2
 *      - user level id = 3 -> 2^(3-1) = 4
 *      - user level id = 4 -> 2^(4-1) = 8
 * - visible_for_levels_editable: it determines if 'visible_for_levels' field is editable from page builder, visibility options can be overriden by custom class and shouldn't be editable in this case.
 * - url: the page url, if it is static page.
 * - content_info: content info object name related to the content on the page,
 *                 if different from module name
 * - meta_description: meta description of the page.
 * - meta_keywords: meta keywords of the page.
 * - meta_robots: instructions for search bots.
 * - cache_lifetime: number of seconds to store cache for.
 * - cache_editable: it determines if cache can be edited from page builder.
 * - deletable: it determines if page can be deleted from page builder.
 * - override_class_name: user defined class name which is derived from BxTemplPage.
 * - override_class_file: the location of the user defined class, leave it empty if class is located in system folders.
 *
 * Page can select appropriate menu automatically if 'module' and 'object' fields in 'sys_objects_page' table are matched with 'module' and 'name' fields in 'sys_menu_items' table.
 *
 *
 * Page layout are stored in 'sys_pages_layouts' table:
 * - name: inner unique layout name.
 * - icon: layout icon to display in page builder, it should represent basic view of the layout to help studio operator determine the layout structure.
 * - title: layout name to display in page builder.
 * - template: template name to use to display page with certain layout.
 * - cells_number: number of areas in the layout, page blocks can be places into this areas(cells).
 * To define areas in the layout they should be named as '__cell_N__', where N is cell number, starting from 1.
 *
 *
 * 2. Add page blocks to 'sys_pages_blocks' table:
 * - object: page object name this block belongs to.
 * - cell_id: cell number in page layout to place block to.
 * - module: module name this block belongs to.
 * - title: block title.
 * - designbox_id: design box to use to diplay page block, it is id of the record from 'sys_pages_design_boxes' table.
 * - visible_for_levels: bit field with set of member level ids. To use member level id in bit field - the level id minus 1 as power of 2 is used, for example:
 *      - user level id = 1 -> 2^(1-1) = 1
 *      - user level id = 2 -> 2^(2-1) = 2
 *      - user level id = 3 -> 2^(3-1) = 4
 *      - user level id = 4 -> 2^(4-1) = 8
 * - type: block type
 *      - raw: HTML block, displayed in page builder as HTML textarea.
 *      - html: HTML block, displayed in page builder as visual editor, like TinyMCE.
 *      - lang: translatable language string, displayed in page builder as editable language key.
 *      - image: just an image, displayed in page builder as HTML upload form.
 *      - rss: RSS block, displayed in page builder as editable URL to RSS resource, along with number of displayed items.
 *      - menu: menu block, displayed as menu selector.
 *      - service: to display block content, the provided service method is used.
 * - content: depending on 'type' field:
 *      - raw: HTML string.
 *      - html: HTML string.
 *      - lang: language key.
 *      - image: image id in the storage and alignment (left, center, right) for example: 36\#center
 *      - rss: URL to RSS with number of displayed items, for example: http://www.example.com/rss#4
 *      - menu: menu object name.
 *      - service: serialized array of service call parameters: module - module name, method - service method name, params - array of parameters.
 * - deletable: is block deletable from page builder
 * - copyable: is block can be copied to any other page from page builder.
 * - order: block order in particular cell.
 *
 * Block design boxes are stored in 'sys_pages_design_boxes' table:
 * - id: consistent id, there are the following defines can be used in the code for each system block style:
 *      - 0 - BX_DB_CONTENT_ONLY: design box with content only - no borders, no background, no caption.
 *      - 1 - BX_DB_DEF: default design box with content, borders and caption.
 *      - 2 - BX_DB_EMPTY: just empty design box, without anything.
 *      - 3 - BX_DB_NO_CAPTION: design box with content, like BX_DB_DEF but without caption.
 *      - 10 - BX_DB_PADDING_CONTENT_ONLY: design box with content only wrapped with default padding - no borders, no background, no caption; it can be used to just wrap content with default padding.
 *      - 11 - BX_DB_PADDING_DEF: default design box with content wrapped with default padding, borders and caption.
 *      - 13 - BX_DB_PADDING_NO_CAPTION: design box with content wrapped with default padding, like BX_DB_DEF but without caption.
 * - title: block name which is displayed in studio, describes block styles.
 * - template: template name to use to display page block.
 *
 *
 * 3. Display Page.
 * Use the following sample code to display page:
 * @code
 *     $oPage = BxDolPage::getObjectInstance('sample'); // 'sample' is 'object' field from 'sys_objects_page' table, it automatically creates instance of default or custom class by object name
 *     if ($oPage)
 *         echo $oPage->getCode(); // print page
 * @endcode
 *
 */
class BxDolPage extends BxDolFactory implements iBxDolFactoryObject, iBxDolReplaceable
{
    protected $_sObject;
    protected $_aObject;
    protected $_oQuery;
    protected $_aMarkers = array ();

    /**
     * Constructor
     * @param $aObject array of page options
     */
    protected function __construct($aObject)
    {
        parent::__construct();

        $this->_sObject = $aObject['object'];
        $this->_aObject = $aObject;
        $this->_oQuery = new BxDolPageQuery($this->_aObject);
    }

    /**
     * Get page object instance by Module name and page URI
     * @param $sModule module name
     * @param $sURI unique page URI
     * @return object instance or false on error
     */
    static public function getObjectInstanceByModuleAndURI($sModule, $sURI = '', $oTemplate = false)
    {
    	if(empty($sURI) && bx_get('i') !== false)
    		$sURI = bx_process_input(bx_get('i'));

    	if(empty($sURI))
			return false;

        $sObject = BxDolPageQuery::getPageObjectNameByURI($sURI, $sModule);
        return $sObject ? self::getObjectInstance($sObject, $oTemplate) : false;
    }

    /**
     * Get page object instance by page URI
     * @param $sURI unique page URI
     * @return object instance or false on error
     */
    static public function getObjectInstanceByURI($sURI = '', $oTemplate = false, $bRedirectCheck = false)
    {
    	if(empty($sURI) && bx_get('i') !== false)
    		$sURI = bx_process_input(bx_get('i'));

    	if(empty($sURI))
			return false;

        $sObject = BxDolPageQuery::getPageObjectNameByURI($sURI);
        if ($bRedirectCheck && !$sObject && '/' == substr($sURI, -1)) {
            header("HTTP/1.1 301 Moved Permanently");
            unset($_GET['i']);
            header ('Location:' . bx_append_url_params(BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=' . trim($sURI, '/')), $_GET));
            exit;
        }

        return $sObject ? self::getObjectInstance($sObject, $oTemplate) : false;
    }

    /**
     * Get page object instance by object name
     * @param $sObject object name
     * @return object instance or false on error
     */
    static public function getObjectInstance($sObject, $oTemplate = false)
    {
        if (isset($GLOBALS['bxDolClasses']['BxDolPage!'.$sObject]))
            return $GLOBALS['bxDolClasses']['BxDolPage!'.$sObject];

        $aObject = BxDolPageQuery::getPageObject($sObject);
        if (!$aObject || !is_array($aObject))
            return false;

        $sClass = 'BxTemplPage';
        if (!empty($aObject['override_class_name'])) {
            $sClass = $aObject['override_class_name'];
            if (!empty($aObject['override_class_file']))
                require_once(BX_DIRECTORY_PATH_ROOT . $aObject['override_class_file']);
        }

        $o = new $sClass($aObject, $oTemplate);

        return ($GLOBALS['bxDolClasses']['BxDolPage!'.$sObject] = $o);
    }

	/**
     * Process page triggers.
     * Page triggers allow to automatically add page blocks to modules with no different if dependant module was install before or after the module page block belongs to.
     * For example module "Notes" adds page blocks to all profiles modules (Persons, Organizations, etc)
     * with no difference if persons module was installed before or after "Notes" module was installed.
     * @param $sPageTriggerName trigger name to process, usually specified in module installer class - @see BxBaseModGeneralInstaller
     * @return always true, always success
     */
    static public function processPageTrigger ($sPageTriggerName)
    {
        // get list of active modules
        $aModules = BxDolModuleQuery::getInstance()->getModulesBy(array(
            'type' => 'modules',
            'active' => 1,
        ));

        // get list of page block triggers
        $aPageBlocks = BxDolPageQuery::getPageTriggers($sPageTriggerName);

        // check each page block trigger for all modules
        foreach ($aPageBlocks as $aPageBlock) {
            foreach ($aModules as $aModule) {
                if (!BxDolRequest::serviceExists($aModule['name'], 'get_page_object_for_page_trigger'))
                    continue;

				$mixedPageObject = BxDolService::call($aModule['name'], 'get_page_object_for_page_trigger', array($sPageTriggerName));
                if (!$mixedPageObject)
                    continue;

                $aPageBlockRow = $aPageBlock;

                if (is_array($mixedPageObject))
                    $aPageBlockRow = array_merge($aPageBlockRow, $mixedPageObject);
                else
                    $aPageBlockRow['object'] = $mixedPageObject;
                
                BxDolPageQuery::addPageBlockToPage($aPageBlockRow);
            }
        }

        return true;
    }

	/**
     * Delete SEO link
     * @param $sModule module name
     * @param $sContentInfoObject content info object
     * @param $sId content id
     * @return number of affected rows
     */
    static public function deleteSeoLink ($sModule, $sContentInfoObject, $sId)
    {
        return BxDolPageQuery::deleteSeoLink($sModule, $sContentInfoObject, $sId);
    }

	/**
     * Delete SEO links by param
     * @param $sParamName GET param name
     * @param $sId content id
     * @return number of affected rows
     */
    static public function deleteSeoLinkByParam ($sParamName, $sId)
    {
        return BxDolPageQuery::deleteSeoLinkByParam($sParamName, $sId);
    }

	/**
     * Delete SEO links by module
     * @param $sModule module name
     * @return number of affected rows
     */
    static public function deleteSeoLinkByModule ($sModule)
    {
        return BxDolPageQuery::deleteSeoLinkByModule($sModule);
    }

	/**
     * Process SEO links. It takes request part from SEO link and process it 
     * to make it work as regular page link
     * @param $sRequest request URI with SEO link
     * @return true - if page was found and processed correctly, false - if page wasn't found
     */
    static public function processSeoLink ($sRequest)
    {
        if (!$sRequest || '/' === $sRequest || !getParam('permalinks_seo_links'))
            return false;

        // redirect to the correct URL
        if ('/' === $sRequest[-1] || $sRequest != mb_strtolower($sRequest)) {
            unset($_GET['_q']);
            $sUrl = BX_DOL_URL_ROOT . bx_append_url_params(mb_strtolower(trim($sRequest, '/')), $_GET);
            header('Location:' . $sUrl, true, 301);
            exit;            
        }

        // parse URL
        $a = explode('/', $sRequest);
        if (!$a || empty($a[0]))
            return false;

        // get page
        $sPageName = BxDolPageQuery::getPageObjectNameByURI($a[0]);
        $aPage = $sPageName ? BxDolPageQuery::getPageObject($sPageName) : false;
        if (!$aPage)
            return false;

        // page with params
        if (!empty($a[1])) { 
            $r = BxDolPageQuery::getSeoLink($aPage['module'], $a[0], ['uri' => $a[1]]);
            if ($r)
                $_GET[$r['param_name']] = $_REQUEST[$r['param_name']] = $r['param_value'];
        }

        // display page
        $_REQUEST['i'] = $_GET['i'] = $a[0];
        $oPage = BxDolPage::getObjectInstanceByURI($a[0], false, true);
        $oPage->displayPage();

        return true;
    }

	/**
     * Transform regular link to SEO link. It takes regular list as param and return SEO link.
     * @param $sLink regular link
     * @param $sPrefix prefix to add to the final URL, usually BX_DOL_URL_ROOT
     * @param $aParams additional GET params
     * @return SEO link string on success, false if transform failed or not necessary
     */
    static public function transformSeoLink ($sLink, $sPrefix, $aParams = array())
    {
        if (!getParam('permalinks_seo_links'))
            return false;

        if (0 !== strncmp('page.php', $sLink, 8)) // only page.php links are supported
            return false;

        $sQuery = parse_url($sLink, PHP_URL_QUERY);
        if (false === $sQuery)
            return false;

        parse_str($sQuery, $aQueryParams);

        $sPageUri = !empty($aQueryParams['i']) ? $aQueryParams['i'] : false;
        unset($aQueryParams['i']);

        if (!$sPageUri) // page URI wasn't found
            return false;

        $aSeoParams = array('id', 'profile_id'); // supported SEO params which are transformed to SEO strings
        $sSeoParamName = '';
        $sSeoParamValue = '';
        foreach ($aSeoParams as $k) {
            if (empty($aQueryParams[$k]) && empty($aParams[$k]))
                continue;
            $sSeoParamName = $k;
            $sSeoParamValue = !empty($aParams[$k]) ? $aParams[$k] : $aQueryParams[$k];
            unset($aQueryParams[$k]);
            unset($aParams[$k]);
            break; // only 1 SEO param will be transformed
        }

        if (!empty($sSeoParamValue) && 0 === strpos($sSeoParamValue, '{'))
            return false;

        $sSeoPageUri = $sPageUri;

        if ($sSeoParamName && $sSeoParamValue) { // process page with SEO param
            $sPageName = BxDolPageQuery::getPageObjectNameByURI($sPageUri);
            $aPage = $sPageName ? BxDolPageQuery::getPageObject($sPageName) : false;
            if ($aPage) {
                $sModule = $aPage['module'];
                $sContentInfo = !empty($aPage['content_info']) ? $aPage['content_info'] : $aPage['module'];
                if ('id' == $sSeoParamName) {
                    $oContentInfo = BxDolContentInfo::getObjectInstance($sContentInfo);
                    $sSeoTitle = $oContentInfo ? $oContentInfo->getContentTitle($sSeoParamValue) : '';
                    if (!$sSeoTitle) 
                        $sSeoTitle = self::getSeoHash($sSeoParamValue);
                }
                elseif ('profile_id' == $sSeoParamName) {
                    $oProfile = BxDolProfile::getInstance($sSeoParamValue);
                    $sSeoTitle = $oProfile ? $oProfile->getDisplayName() : self::getSeoHash($sSeoParamValue);
                }
                
                $r = BxDolPageQuery::getSeoLink($sModule, $sPageUri, ['param_value' => $sSeoParamValue]);
                if (!$r) {
                    $sSeoTitleLimited = BxTemplFunctions::getInstance()->getStringWithLimitedLength($sSeoTitle, 45);
                    $sUri = uriGenerate ($sSeoTitleLimited, 'sys_seo_links', 'uri', '-', '-', ['module' => $sModule, 'page_uri' => $sPageUri]);

                    bx_alert('system', 'uri-generate', 0, false, [
                        'module' => $sModule,
                        'page_uri' =>$sPageUri,
                        'param_name' => $sSeoParamName,
                        'param_value' => $sSeoParamValue,
                        'title' => $sSeoTitle,
                        'uri' => &$sUri,
                    ]);

                    BxDolPageQuery::insertSeoLink($sModule, $sPageUri, $sSeoParamName, $sSeoParamValue, $sUri);
                }
                elseif ($r['param_name'] == $sSeoParamName) {
                    $sUri = $r['uri'];
                } 
                else {
                    $sUri = false;
                }

                if ($sUri)
                    $sSeoPageUri .= '/' . $sUri;
                else
                    $sSeoPageUri = false;
            }
        }

        if (!$sSeoPageUri)
            return false;

        return $sPrefix . bx_append_url_params($sSeoPageUri, array_merge($aQueryParams, $aParams));
    }

	/**
     * Transform SEO regular link into regular link with permalinks off.
     * @param $sSeoLink SEO link
     * @param $sPrefix prefix to add to the final URL, usually BX_DOL_URL_ROOT
     * @return unSEO link string on success, false if no transform is needed
     */
    static public function untransformSeoLink ($sSeoLink, $sPrefix)
    {
        // check for standard links first
        if (preg_match('/^(page\/|s\/|m\/|modules\/|page\.php|storage\.php)/', $sSeoLink))
            return false;

        // parse link
        $aParts = parse_url($sSeoLink);
        if (!$aParts || empty($aParts['path']))
            return false;

        $aUris = explode('/', trim($aParts['path'], '/'));

        // check if link starts with page URI and page with this URI exists 
        if (!$aUris || empty($aUris[0]) || !($sPageName = BxDolPageQuery::getPageObjectNameByURI($aUris[0])))
            return false;

        // make final URL
        $s = 'page.php?i=' . $aUris[0];

        // add params
        if (!empty($aUris[1])) {
            $aPage = BxDolPageQuery::getPageObject($sPageName);
            if ($aPage) {
                $r = BxDolPageQuery::getSeoLink($aPage['module'], $aUris[0], ['uri' => urldecode($aUris[1])]);     
                if (!$r)
                    return false;
                $s .= '&' . $r['param_name'] . '=' .  $r['param_value'];
            }
        }

        return $s . (!empty($aParts['query']) ? '&' . $aParts['query'] : '');
    }

	/**
     * Perform SEO redorect from regular pages if needed
     * @param $sSeoLink SEO link
     * @param $sPrefix prefix to add to the final URL, usually BX_DOL_URL_ROOT
     * @return unSEO link string on success, false if no transform is needed
     */
    static public function seoRedirect ()
    {
        if (!getParam('permalinks_seo_links'))
            return;

        list($sPageLink, $aPageParams) = bx_get_base_url_inline();
        $sLink = bx_append_url_params($sPageLink, $aPageParams);
        if (0 === strpos($sLink, BX_DOL_URL_ROOT))
            $sLink = substr($sLink, strlen(BX_DOL_URL_ROOT));

        if ($sSeoLink = self::transformSeoLink ($sLink, BX_DOL_URL_ROOT)) {
            header("Location:{$sSeoLink}", true, 301);
            exit;
        }
    }

    static public function getSeoHash($s)
    {
        return base_convert(substr(md5($s), -8), 16, 36);
    }
    
    /**
     * Static method to Get embed code
     * @return string.
     */
    static public function getEmbedData ($sUrl)
    {
        $sUrl = urldecode($sUrl);
        $aParams = [];
        
        $aUrl = bx_get_base_url($sUrl);
        if (!isset($aUrl[1]['i'])){
            return [];
        }
            
        $sUri = $aUrl[1]['i'];
        unset($aUrl[1]['i']);
        $aParams = $aUrl[1];

        $sAuthorName = $sAuthorUrl = $sThumb = '';
        if (isset($aParams['id'])){
            $sContentInfoObject = BxDolPageQuery::getContentInfoObjectNameByURI($sUri);
            $oContentInfo = BxDolContentInfo::getObjectInstance($sContentInfoObject);

            $sTitle = $oContentInfo->getContentTitle($aParams['id']);
            $iAuthor = $oContentInfo->getContentAuthor($aParams['id']);
            $sAuthorName = BxDolProfile::getInstance()->getDisplayName($iAuthor);
            $sAuthorUrl = BxDolProfile::getInstance()->getUrl($iAuthor);
            $sThumb = $oContentInfo->getContentThumb($aParams['id']);
            $sHtml = $oContentInfo->getContentEmbed($aParams['id']);

        }
        else{
            $oPage = BxDolPage::getObjectInstanceByURI($sUri, false, true);
            $aPage = $oPage->getObject();
            $sTitle = $oPage->_getPageTitle();
            $sHtml = BxDolTemplate::getInstance()->parseHtmlByName('embed.html', [
                'title' => $sTitle,
                'url' => BX_DOL_URL_ROOT . 'page.php?a=embed&o=' . $oPage->getName()
            ]);
        }
        return ['url' => $sUrl, 'title' => $sTitle, 'author_name' => $sAuthorName, 'author_url' => $sAuthorUrl, 'thumbnail_url' => $sThumb, 'html' => $sHtml];
    }

    /**
     * Display complete page
     */
    public function displayPage ($oTemplate = null)
    {
        if (!$oTemplate)
            $oTemplate = BxDolTemplate::getInstance();

        $oTemplate->setPageNameIndex (BX_PAGE_DEFAULT);
        $oTemplate->setPageUrl($this->_aObject['url']);
        $oTemplate->setPageType ($this->getType());
        $oTemplate->setPageInjections ($this->getInjections());
        $oTemplate->setPageContent ('page_main_code', $this->getCode());
        $oTemplate->getPageCode();
    }

    public function getId ()
    {
    	return (int)$this->_aObject['id'];
    }

    public function getType ()
    {
    	return (int)$this->_aObject['type_id'];
    }

    public function getModule ()
    {
    	return $this->_aObject['module'];
    }
    
    public function getSubMenu ()
    {
        return $this->_aObject['submenu'];
    }

    public function getName ()
    {
    	return $this->_sObject;
    }
    
    public function getObject ()
    {
    	return $this->_aObject;
    }
    
    public function getInjections()
    {
        $aResult = array();

        foreach($this->_aObject as $sKey => $sValue)
            if(strpos($sKey, 'inj_') === 0)
                $aResult[substr($sKey, 4)] = $sValue;

        return $aResult;
    }

    /**
     * Add replace markers. Markers are replaced in raw, html, lang blocks and page title, description, keywords and block titles.
     * @param $a array of markers as key => value
     * @return true on success or false on error
     */
    public function addMarkers ($a)
    {
        if (empty($a) || !is_array($a))
            return false;
        $this->_aMarkers = array_merge ($this->_aMarkers, $a);
        return true;
    }

    public function isVisiblePage ()
    {
        return $this->_isVisiblePage($this->_aObject);
    }
    
    public function isEditAllowed ()
    {
        return false;
    }

    public function isDeleteAllowed ()
    {
        return false;
    }

    /**
     * Replace provided markers in a string
     * @param $mixed string or array to replace markers in
     * @return string where all occured markers are replaced
     */
    protected function _replaceMarkers ($mixed, $aAdditionalMarkers = array())
    {
        return bx_replace_markers($mixed, array_merge($this->_aMarkers, $aAdditionalMarkers));
    }

    /**
     * Check if page block is visible.
     */
    protected function _isVisibleBlock ($a)
    {
        return BxDolAcl::getInstance()->isMemberLevelInSet($a['visible_for_levels']);
    }

    /**
     * Check if page is visible.
     */
    protected function _isVisiblePage ($a)
    {
        return isAdmin() || BxDolAcl::getInstance()->isMemberLevelInSet($a['visible_for_levels']);
    }

}

/** @} */
