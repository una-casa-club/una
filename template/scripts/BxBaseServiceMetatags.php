<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaBaseView UNA Base Representation Classes
 * @{
 */

define('BX_METATAGS_KEYWORDS_IN_CLOUD', 32); ///< default number of tags in tags cloud

/**
 * System services for metatags functionality.
 */
class BxBaseServiceMetatags extends BxDol
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-metatags Metatags
     * @subsubsection bx_system_general-keywords_cloud keywords_cloud
     * 
     * @code bx_srv('system', 'keywords_cloud', ["bx_posts", "bx_posts"], 'TemplServiceMetatags'); @endcode
     * @code {{~system:keywords_cloud:TemplServiceMetatags["bx_posts", "bx_posts"]~}} @endcode
     * 
     * Get keywords cloud.
     * @param $sObject metatags object to get keywords cloud for
     * @param $mixedSection search section to refer when keyword is clicked, 
     *          set the same as $sObject to show content withing the module only, 
     *          it can be one value or array of values, leave empty to show 
     *          all possible content upon keyword click
     * @param $aParams additional params:
     *          - max_count: number of tags in keywords cloud, 
     *                  by default BX_METATAGS_KEYWORDS_IN_CLOUD
     *          - show_empty: show empty message or not when no data available
     *          
     * @return tags cloud HTML string
     * 
     * @see BxBaseServiceMetatags::serviceKeywordsCloud
     */
    /** 
     * @ref bx_system_general-keywords_cloud "keywords_cloud"
     */
    public function serviceKeywordsCloud($sObject, $mixedSection, $aParams = array())
    {
    	$iMaxCount = isset($aParams['max_count']) ? (int)$aParams['max_count'] : BX_METATAGS_KEYWORDS_IN_CLOUD;
    	$bShowEmpty = isset($aParams['show_empty']) ? (bool)$aParams['show_empty'] : false;

        $aContextInfo = bx_get_page_info();
        if (isset($aContextInfo['context_module']) && isset($aContextInfo['profile_context_id'])){
            $sResult = BxDolMetatags::getObjectInstance($sObject)->getKeywordsCloud($mixedSection, $iMaxCount, false, ['context_id' => $aContextInfo['profile_context_id']]);
        }
        else{
            $sResult = BxDolMetatags::getObjectInstance($sObject)->getKeywordsCloud($mixedSection, $iMaxCount);
        }
        if(empty($sResult))
			return $bShowEmpty ? MsgBox(_t('_Empty')) : '';

		return $sResult;
    }

    public function serviceBrowseLabels($aParams = array())
    {
        return BxDolLabel::getInstance()->getLabelsBrowse($aParams);
    }

    /**
     * Get location map.
     * @param $sObject metatgs object to get keywords cloud for
     * @param $iId content id
     * @return map HTML string
     */
    public function serviceLocationsMap($sObject, $iId, $aParams = array())
    {
        return BxDolMetatags::getObjectInstance($sObject)->getLocationsMap($iId, $aParams);
    }
    
	/**
     * Get notification data for Notifications module - action Mention. 
     */
	public function serviceGetNotificationsPostMention($aEvent)
    {
    	$iProfile = (int)$aEvent['object_owner_id'];
    	$oProfile = BxDolProfile::getInstance($iProfile);
        if(!$oProfile)
			return array();

		return array(
			'entry_sample' => '_sys_profile_sample_single',
			'entry_url' => BX_DOL_URL_ROOT . bx_append_url_params('searchKeyword.php', array('type' => 'mention', 'keyword' => $iProfile)),
			'entry_caption' => $oProfile->getDisplayName(),
			'entry_author' => $iProfile,
			'lang_key' => '_sys_metatags_mention_added',
		);
    }
}

/** @} */
