<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

define('BX_PROFILE_ACTION_AUTO', 0); ///< automatic action without any checking
define('BX_PROFILE_ACTION_MANUAL', 1); ///< manual action performed by human
define('BX_PROFILE_ACTION_ROBOT', 2); ///< action peformed by some robot based on some conditions
define('BX_PROFILE_ACTION_EXTERNAL', 2); ///< action peformed by external service, like join using OAuth 

class BxDolProfile extends BxDolFactory implements iBxDolProfile
{
    protected $_iProfileID;
    protected $_aProfile;
    protected $_oQuery;

    /**
     * Constructor
     */
    protected function __construct ($iProfileId)
    {
        $iProfileId = (int)$iProfileId;
        $sClass = get_class($this) . '_' . $iProfileId;
        if (isset($GLOBALS['bxDolClasses'][$sClass]))
            trigger_error ('Multiple instances are not allowed for the class: ' . get_class($this), E_USER_ERROR);

        parent::__construct();

        $this->_iProfileID = $iProfileId; // since constructor is protected $iProfileId is always valid
        $this->_oQuery = BxDolProfileQuery::getInstance();
    }

    /**
     * Prevent cloning the instance
     */
    public function __clone()
    {
        $sClass = get_class($this) . '_' . $this->_iProfileID;
        if (isset($GLOBALS['bxDolClasses'][$sClass]))
            trigger_error('Clone is not allowed for the class: ' . get_class($this), E_USER_ERROR);
    }

    /**
     * Get singleton instance of Account Profile by account id
     */
    public static function getInstanceAccountProfile($iAccountId = false, $bClearCache = false)
    {
        if (!$iAccountId)
            $iAccountId = getLoggedId();
        $oQuery = BxDolProfileQuery::getInstance();
        $aProfile = $oQuery->getProfileByContentTypeAccount($iAccountId, 'system', $iAccountId);
        if (!$aProfile)
            return false;
        return self::getInstance($aProfile['id'], $bClearCache);
    }

    /**
     * Get singleton instance of Profile by account id, content id and type
     */
    public static function getInstanceByContentTypeAccount($iContent, $sType, $iAccountId = false)
    {
        if (!$iAccountId)
            $iAccountId = getLoggedId();
        $oQuery = BxDolProfileQuery::getInstance();
        $aProfile = $oQuery->getProfileByContentTypeAccount($iContent, $sType, $iAccountId);
        if (!$aProfile)
            return false;
        return self::getInstance($aProfile['id']);
    }

    /**
     * Get singleton instance of Profile by content id and type
     */
    public static function getInstanceByContentAndType($iContent, $sType, $bClearCache = false)
    {
        $oQuery = BxDolProfileQuery::getInstance();
        $aProfile = $oQuery->getProfileByContentAndType($iContent, $sType, $bClearCache);
        if (!$aProfile)
            return false;
        return self::getInstance($aProfile['id']);
    }

    /**
     * Get singleton instance of Profile by Account id (currently active profile is returned)
     */
    public static function getInstanceByAccount($iAccountId = false, $bClearCache = false)
    {
        $oQuery = BxDolProfileQuery::getInstance();
        $mixedProfileId = $oQuery->getCurrentProfileByAccount($iAccountId, $bClearCache);

        return self::getInstance($mixedProfileId);
    }

    /**
     * Get singleton instance of Profile by profile id, if profile isn't found it returns instance of BxDolProfileAnonymous or BxDolProfileUndefined
     */
    public static function getInstanceMagic($mixedProfileId = false, $bClearCache = false)
    {
        if ($mixedProfileId < 0)
            return BxDolProfileAnonymous::getInstance($mixedProfileId);

        if (0 === $mixedProfileId || !($oProfile = self::getInstance($mixedProfileId, $bClearCache)))
            return BxDolProfileUndefined::getInstance();
        
        return $oProfile;
    }

    /**
     * Get singleton instance of Profile by profile id
     */
    public static function getInstance($mixedProfileId = false, $bClearCache = false)
    {
        $oQuery = BxDolProfileQuery::getInstance();

        if (!$mixedProfileId)
            $mixedProfileId = $oQuery->getCurrentProfileByAccount(getLoggedId(), $bClearCache);

        $aProfileInfo = $oQuery->getInfoById($mixedProfileId);
        if (empty($aProfileInfo['id']) || !BxDolModuleDb::getInstance()->isEnabledByName($aProfileInfo['type']))
            return false;

        $sClass = __CLASS__ . '_' . $aProfileInfo['id'];
        if (!isset($GLOBALS['bxDolClasses'][$sClass]))
            $GLOBALS['bxDolClasses'][$sClass] = new BxDolProfile($aProfileInfo['id']);

        return $GLOBALS['bxDolClasses'][$sClass];
    }

    /**
     * Get profile id
     */
    public function id()
    {
        $aProfile = $this->getInfo($this->_iProfileID);
        return isset($aProfile['id']) ? $aProfile['id'] : false;
    }

    /**
     * Get account id associated with the profile
     */
    public function getAccountId()
    {
        $aInfo = $this->getInfo();
        return $aInfo['account_id'];
    }

    /**
     * Get account object associated with the profile
     */
    public function getAccountObject()
    {
        return BxDolAccount::getInstance($this->getAccountId());
    }

    /**
     * Get content id associated with the profile
     */
    public function getContentId()
    {
        $aInfo = $this->getInfo();
        return $aInfo['content_id'];
    }

    /**
     * Check if profile status is active
     */
    public function isActive($iProfileId = false)
    {
        if($this->getStatus($iProfileId) != BX_PROFILE_STATUS_ACTIVE)
            return false;

        return (($oAccount = $this->getAccountObject()) !== false && $oAccount->isConfirmed()) || !getParam('sys_account_hide_unconfirmed_accounts');
    }

    /**
     * Is profile online
     */
    public function isOnline($iProfileId = false)
    {
        $iProfileId = (int)$iProfileId ? $iProfileId : $this->_iProfileID;
        return $this->_oQuery->isOnline($iProfileId);
    }

    /**
     * Is profile can 'Act as Profile'
     */
    public function isActAsProfile($iProfileId = false)
    {
        $aInfo = $this->_oQuery->getInfoById((int)$iProfileId ? $iProfileId : $this->_iProfileID);
        return BxDolService::call($aInfo['type'], 'act_as_profile');
    }

    /**
     * Get profile status
     */
    public function getStatus($iProfileId = false)
    {
        $aInfo = $this->_oQuery->getInfoById((int)$iProfileId ? $iProfileId : $this->_iProfileID);
        return $aInfo['status'];
    }

    /**
     * Get profile module name
     */
    public function getModule($iProfileId = false)
    {
        $aInfo = $this->_oQuery->getInfoById((int)$iProfileId ? $iProfileId : $this->_iProfileID);
        return $aInfo['type'];
    }

    /**
     * Get profile info
     */
    public function getInfo($iProfileId = 0)
    {
        if ($iProfileId && $iProfileId != $this->_iProfileID)
            return $this->_oQuery->getInfoById((int)$iProfileId ? $iProfileId : $this->_iProfileID);

        if ($this->_aProfile)
            return $this->_aProfile;
        
        $this->_aProfile = $this->_oQuery->getInfoById((int)$iProfileId ? $iProfileId : $this->_iProfileID);
        return $this->_aProfile;
    }

    /**
     * Validate profile id.
     * @param $s - profile id
     * @return profile id or false if profile was not found
     */
    static public function getID($s)
    {
        $iId = BxDolProfileQuery::getInstance()->getIdById((int)$s);
        return $iId ? $iId : false;
    }

    /**
     * Get name to display in thumbnail
     */
    public function getDisplayName($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        $sDisplayName = BxDolService::call($aInfo['type'], 'profile_name', array($aInfo['content_id']));
        bx_alert('profile', 'profile_name', $iProfileId, 0, array('info' => $aInfo, 'display_name' => &$sDisplayName));
        return $sDisplayName;
    }

    /**
     * Get profile url
     */
    public function getUrl($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'profile_url', array($aInfo['content_id']));
    }

    /**
     * Get profile unit
     */
    public function getUnit($iProfileId = 0, $aParams = array())
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'profile_unit', array($aInfo['content_id'], $aParams));
    }
    
    /**
     * Get badges
     */
    public function getBadges($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'get_badges', array($aInfo['content_id'], true, true));
    }

	/**
     * Check whether a profile has real image uploaded by user.
     */
    public function hasImage($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'has_image', array($aInfo['content_id']));
    }

    /**
     * Get picture url
     */
    public function getPicture($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'profile_picture', array($aInfo['content_id']));
    }

    /**
     * Get avatar url
     */
    public function getAvatar($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'profile_avatar', array($aInfo['content_id']));
    }

    /**
     * Get big (2x) avatar url
     */
    public function getAvatarBig($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'profile_avatar_big', array($aInfo['content_id']));
    }

    /**
     * Get cover url
     */
    public function getCover($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'profile_cover', array($aInfo['content_id']));
    }

	/**
     * Get unit cover url
     */
    public function getUnitCover($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'profile_unit_cover', array($aInfo['content_id']));
    }

    /**
     * Get thumbnail url
     */
    public function getThumb($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'profile_thumb', array($aInfo['content_id']));
    }

    /**
     * Get icon url
     */
    public function getIcon($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'profile_icon', array($aInfo['content_id']));
    }

    /**
     * Get module icon
     */
    public function getIconModule($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'module_icon');
    }

    /**
     * get profile edit page url
     */
    public function getEditUrl($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'profile_edit_url', array($aInfo['content_id']));
    }

    /**
     * @see iBxDolProfile::checkAllowedProfileView
     */
    public function checkAllowedProfileView($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'check_allowed_profile_view', array($aInfo['content_id']));
    }

    /**
     * @see iBxDolProfile::checkAllowedProfileContact
     */
    public function checkAllowedProfileContact($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'check_allowed_profile_contact', array($aInfo['content_id']));
    }
    
    
    /**
     * @see iBxDolProfile::checkAllowedPostInProfile
     */
    public function checkAllowedPostInProfile($iProfileId = 0)
    {
        $aInfo = $this->getInfo($iProfileId);
        return BxDolService::call($aInfo['type'], 'check_allowed_post_in_profile', array($aInfo['content_id']));
    }
    
    /**
     * Delete profile.
     * @param $ID - optional profile id to delete
     * @param $bDeleteWithContent - delete profile with all its contents
     * @param $bForceDelete - force deletetion is case of account profile deletion
     * @return false on error, or true on success
     */
    function delete($ID = false, $bDeleteWithContent = false, $bForceDelete = false)
    {
        $ID = (int)$ID;
        if (!$ID)
            $ID = $this->_iProfileID;

        $aProfileInfo = $this->_oQuery->getInfoById($ID);
        if (!$aProfileInfo)
            return false;

        // delete system profiles (accounts) is not allowed, instead - delete whole account
        if (!$bForceDelete && 'system' == $aProfileInfo['type'])
            return false;

        // delete actual profile
        if ($sErrorMsg = BxDolService::call($aProfileInfo['type'] , 'delete_entity_service', array($aProfileInfo['content_id'], $bDeleteWithContent)))
            return false;

        // switch profile context if deleted profile is active profile context
        $oAccount = BxDolAccount::getInstance ($aProfileInfo['account_id']);
        $aAccountInfo = $oAccount->getInfo();
        if (!$bForceDelete && $ID == $aAccountInfo['profile_id'])
            $oAccount->updateProfileContextAuto($ID);

        // create system event before deletion
        $isStopDeletion = false;
        bx_alert('profile', 'before_delete', $ID, 0, array('delete_with_content' => $bDeleteWithContent, 'stop_deletion' => &$isStopDeletion));
        if ($isStopDeletion)
            return false;

        // delete associated content
        if($bDeleteWithContent) {
	        BxDolCmts::onAuthorDelete($ID);

	        BxDolReport::onAuthorDelete($ID);

	        BxDolVote::onAuthorDelete($ID);

	        BxDolFavorite::onAuthorDelete($ID);

	        BxDolView::onAuthorDelete($ID);
        }

        // delete connections
        $oConn = BxDolConnection::getObjectInstance('sys_profiles_friends');
        $oConn->onDeleteInitiatorAndContent($ID);

        $oConn = BxDolConnection::getObjectInstance('sys_profiles_subscriptions');
        $oConn->onDeleteInitiatorAndContent($ID);

        // delete profile's acl levels
        BxDolAcl::getInstance()->onProfileDelete($ID);

        // delete profile
        if (!$this->_oQuery->delete($ID))
            return false;

        // create system event
        bx_alert('profile', 'delete', $ID, 0, array('delete_with_content' => $bDeleteWithContent));

        // unset class instance to prevent creating the instance again
        $this->_iProfileID = 0;
        $sClass = get_class($this) . '_' . $ID;
        unset($GLOBALS['bxDolClasses'][$sClass]);

        return true;
    }

    /**
     * Insert account and content id association. Also if currect profile id is not defined - it updates current profile id in account.
     * @param $iAccountId account id
     * @param $iContentId content id
     * @param $sStatus profile status
     * @param $sType profile content type
     * @return inserted profile's id
     */
    static public function add ($iAction, $iAccountId, $iContentId, $sStatus, $sType = 'system')
    {
        $oQuery = BxDolProfileQuery::getInstance();
        if (!($iProfileId = $oQuery->insertProfile ($iAccountId, $iContentId, $sStatus, $sType)))
            return false;
        bx_alert('profile', 'add', $iProfileId, 0, array('module' => $sType, 'content' => $iContentId, 'account' => $iAccountId, 'status' => $sStatus, 'action' => $iAction, 'profile_id' => &$iProfileId));
        return $iProfileId;
    }

    /**
     * Change profile status to 'Active'
     */
    public function activate($iAction, $iProfileId = 0, $bSendEmailNotification = true)
    {
        $sStatus = $this->getStatus($iProfileId);
        return $this->changeStatus(BX_PROFILE_STATUS_ACTIVE, BX_PROFILE_STATUS_PENDING == $sStatus ? 'approve' : 'activate', $iAction, $iProfileId, $bSendEmailNotification);
    }

    /**
     * Change profile status from 'Pending' to the next level - 'Active'
     */
    public function approve($iAction, $iProfileId = 0, $bSendEmailNotification = true)
    {
        return $this->changeStatus(BX_PROFILE_STATUS_ACTIVE, 'approve', $iAction, $iProfileId, $bSendEmailNotification);
    }

    /**
     * Change profile status to 'Pending'
     */
    public function disapprove($iAction, $iProfileId = 0, $bSendEmailNotification = true)
    {
        return $this->changeStatus(BX_PROFILE_STATUS_PENDING, 'disapprove', $iAction, $iProfileId, $bSendEmailNotification);
    }

    public function doAudit($sAction, $aData = array())
    {
        bx_audit(
            $this->getContentId(), 
            $this->getModule(), 
            $sAction,  
            array('content_title' => $this->getDisplayName(), 'data' => $aData)
        );  
    }
    
    /**
     * Change profile status to 'Suspended'
     */
    public function suspend($iAction, $iProfileId = 0, $bSendEmailNotification = true)
    {
        //moderators shouldn't be able to suspend other moderators and admins
        if (BxDolAcl::getInstance()->isMemberLevelInSet(array(MEMBERSHIP_ID_MODERATOR), bx_get_logged_profile_id()) && BxDolAcl::getInstance()->isMemberLevelInSet(array(MEMBERSHIP_ID_MODERATOR, MEMBERSHIP_ID_ADMINISTRATOR), $iProfileId))
            return false;
        
        return $this->changeStatus(BX_PROFILE_STATUS_SUSPENDED, 'suspend', $iAction, $iProfileId, $bSendEmailNotification);
    }

    protected function changeStatus($sStatus, $sAlertActionName, $iAction, $iProfileId = 0, $bSendEmailNotification = true)
    {
        if (!$iProfileId)
            $iProfileId = $this->_iProfileID;

        // get account and profile objects
        $oProfile = BxDolProfile::getInstance($iProfileId);
        $oAccount = $oProfile->getAccountObject();
        if (!$oProfile || !$oAccount)
            return false;

        // change status
        if (!$this->_oQuery->changeStatus($iProfileId, $sStatus))
            return false;

        $this->_aProfile = array();

        // alert about status changing
        bx_alert('profile', $sAlertActionName, $iProfileId, false, array('action' => $iAction));
        
        $this->doAudit('_sys_audit_action_set_status_' . $sStatus);
        
        // send email to member about status change
        if ($bSendEmailNotification)
            sendMailTemplate('t_ChangeStatus' . ucfirst($sStatus), $oAccount->id(), $iProfileId, array('status' => $sStatus), BX_EMAIL_SYSTEM);

        return true;
    }
    
    /**
     * Display informer message if it is possible to switch to this profile
     */
    public function checkSwitchToProfile($oTemplate = null, $iViewerAccountId = false, $iViewerProfileId = false)
    {
        if (false === $iViewerAccountId)
            $iViewerAccountId = getLoggedId();
        if (false === $iViewerProfileId)
            $iViewerProfileId = bx_get_logged_profile_id();

        if (!$iViewerAccountId || !$iViewerProfileId)
            return;

        $aCheck = checkActionModule($iViewerProfileId, 'switch to any profile', 'system', false);
        $bAllowSwitchToAnyProfile = $aCheck[CHECK_ACTION_RESULT] === CHECK_ACTION_RESULT_ALLOWED;
        
        $iSwitchToAccountId = $this->getAccountId();
        $iSwitchToProfileId = $this->id();
        $bCanSwitch = ($iSwitchToAccountId == $iViewerAccountId || $bAllowSwitchToAnyProfile);
        bx_alert('account', 'check_switch_context', $iSwitchToAccountId, $iViewerProfileId, array('switch_to_profile' => $iSwitchToProfileId, 'viewer_account' => $iViewerAccountId, 'override_result' => &$bCanSwitch));

        if (!$bCanSwitch ||  $iViewerProfileId == $iSwitchToProfileId)
            return;

        $oInformer = BxDolInformer::getInstance($oTemplate);
        if ($oInformer){
            if ($iSwitchToAccountId == $iViewerAccountId){
                $oInformer->add('sys-switch-profile-context', _t('_sys_txt_account_profile_context_change_suggestion', BxDolPermalinks::getInstance()->permalink('page.php?i=account-profile-switcher', array('switch_to_profile' => $this->id(), 'redirect_back' => 1))), BX_INFORMER_INFO);
            }
            else{
                $oInformer->add('sys-switch-profile-context', _t('_sys_txt_account_profile_context_change_to_another_suggestion', BxDolPermalinks::getInstance()->permalink('page.php?i=account-profile-switcher', array('switch_to_profile' => $this->id(), 'redirect_back' => 1))), BX_INFORMER_INFO);
            }
        }
    }

	/**
     * Add permament messages.
     */
    public function addInformerPermanentMessages ($oInformer)
    {
    	$aInfo = $this->getInfo();
    	$aProfiles = $this->_oQuery->getProfilesByAccount($aInfo['account_id']);

        if ($aInfo['type'] == 'system' && count($aProfiles) == 1) {
            $sUrl = BxDolPermalinks::getInstance()->permalink('page.php?i=account-profile-switcher');
            $oInformer->add('sys-account-profile-system', _t('_sys_txt_account_profile_system', $sUrl), BX_INFORMER_ALERT);
        }
    }
}

/** @} */
