<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Payment Payment
 * @ingroup     UnaModules
 *
 * @{
 */

require_once('BxPaymentProviderChargebee.php');

class BxPaymentProviderChargebeeV3 extends BxPaymentProviderChargebee
{
    function __construct($aConfig)
    {
        $this->MODULE = 'bx_payment';

        parent::__construct($aConfig);

        $this->_aIncludeJs = array(
            'https://js.chargebee.com/v2/chargebee.js',
            'main.js',
            'chargebee_v3.js'
        );

        $this->_aIncludeCss = array(
            'chargebee_v3.css'
        );
    }

    public function actionGetHostedPage($iClientId, $iVendorId, $sItemName)
    {
        $this->initOptionsByVendor($iVendorId);

        $aItem = array('name' => $sItemName);

        $mixedItemAddons = bx_process_input(bx_get('addons'));
        if(!empty($mixedItemAddons)) {
            $aItemAddons = is_array($mixedItemAddons) ? $mixedItemAddons : $this->_oModule->_oConfig->s2a($mixedItemAddons);

            foreach($aItemAddons as $sItemAddon)
                if(!isset($aItem['addons'][$sItemAddon]))
                    $aItem['addons'][$sItemAddon] = array(
                        'id' => $sItemAddon,
                        'quantity' => 1
                    );
                else 
                    $aItem['addons'][$sItemAddon]['quantity'] += 1;

            $aItem['addons'] = array_values($aItem['addons']);
        }
        $aClient = $this->_oModule->getProfileInfo($iClientId);

        $oPage = $this->createHostedPage($aItem, $aClient);
        if($oPage === false)
            return echoJson(array());

        header('Content-type: text/html; charset=utf-8');
        echo $oPage->toJson();
    }

    public function actionGetPortal($iPendingId)
    {
    	if(!isLogged())
    		return echoJson(array());

    	$aPending = $this->_oModule->_oDb->getOrderPending(array('type' => 'id', 'id' => $iPendingId));
    	if(empty($aPending) || !is_array($aPending))
    		return echoJson(array());

    	$this->initOptionsByVendor((int)$aPending['seller_id']);

    	$aSubscription = $this->_oModule->_oDb->getSubscription(array('type' => 'pending_id', 'pending_id' => $iPendingId));
    	if(empty($aSubscription) || !is_array($aSubscription))
    		return echoJson(array());

    	$oPortal = $this->getPortal($aSubscription['customer_id'], $aSubscription['subscription_id']);
    	if($oPortal === false)
    		return echoJson(array());

    	header('Content-type: text/html; charset=utf-8');
    	echo $oPortal->toJson();
    }

    public function addJsCss()
    {
    	if(!$this->isActive())
    		return;

        $this->_oModule->_oTemplate->addJs($this->_aIncludeJs);
        $this->_oModule->_oTemplate->addCss($this->_aIncludeCss);
    }

    public function initializeCheckout($iPendingId, $aCartInfo, $sRedirect = '')
    {
        $sPageId = bx_process_input(bx_get('page_id'));
        if(empty($sPageId) || empty($iPendingId))
        	return $this->_sLangsPrefix . 'err_wrong_data';

    	$aItem = array_shift($aCartInfo['items']);
    	if(empty($aItem) || !is_array($aItem))
    		return $this->_sLangsPrefix . 'err_empty_items';

		$aClient = $this->_oModule->getProfileInfo();
		$aVendor = $this->_oModule->getProfileInfo($aCartInfo['vendor_id']);

		$oPage = $this->retreiveHostedPage($sPageId);
		if($oPage === false)
			return $this->_sLangsPrefix . 'err_cannot_perform';

		$aPending = $this->_oModule->_oDb->getOrderPending(array('type' => 'id', 'id' => $iPendingId));
		if(!empty($aPending['order']) || !empty($aPending['error_code']) || !empty($aPending['error_msg']) || (int)$aPending['processed'] != 0)
            return $this->_sLangsPrefix . 'err_already_processed';

        if($aPending['type'] != BX_PAYMENT_TYPE_RECURRING) 
            return $this->_sLangsPrefix . 'err_wrong_data';

		$oCustomer = $oPage->content()->customer();
		$oSubscription = $oPage->content()->subscription();

		return array(
			'code' => 0,
			'eval' => $this->_oModule->_oConfig->getJsObject('cart') . '.onSubscribeSubmit(oData);',
			'redirect' => $this->getReturnDataUrl($aVendor['id'], array(
				'order_id' => $oSubscription->id,
				'customer_id' => $oCustomer->id,
				'pending_id' => $aPending['id'],
				'redirect' => $sRedirect
			))
		);
    }

    public function finalizeCheckout(&$aData)
    {
        $sOrderId = bx_process_input($aData['order_id']);
    	$sCustomerId = bx_process_input($aData['customer_id']);
        $iPendingId = bx_process_input($aData['pending_id'], BX_DATA_INT);
        if(empty($iPendingId))
            return array('code' => 1, 'message' => $this->_sLangsPrefix . 'err_wrong_data');

        $sRedirect = bx_process_input($aData['redirect']);

        $aPending = $this->_oModule->_oDb->getOrderPending(array('type' => 'id', 'id' => $iPendingId));
        if(!empty($aPending['order']) || !empty($aPending['error_code']) || !empty($aPending['error_msg']) || (int)$aPending['processed'] != 0)
            return array('code' => 3, 'message' => $this->_sLangsPrefix . 'err_already_processed');

        if($aPending['type'] != BX_PAYMENT_TYPE_RECURRING) 
            return array('code' => 1, 'message' => $this->_sLangsPrefix . 'err_wrong_data');

        $oCustomer = $this->retrieveCustomer($sCustomerId);
        $oSubscription = $this->retrieveSubscription($sOrderId);
        if($oCustomer === false || $oSubscription === false)
            return array('code' => 4, 'message' => $this->_sLangsPrefix . 'err_cannot_perform');

        $aResult = array(
            'code' => BX_PAYMENT_RESULT_SUCCESS,
            'message' => $this->_sLangsPrefix . 'cbee_msg_subscribed',
            'pending_id' => $iPendingId,
            'customer_id' => $oCustomer->id,
            'subscription_id' => $oSubscription->id,
            'client_name' => _t($this->_sLangsPrefix . 'txt_buyer_name_mask', $oCustomer->firstName, $oCustomer->lastName),
            'client_email' => $oCustomer->email,
            'paid' => false,
            'trial' => $oSubscription->status == 'in_trial',
            'redirect' => $sRedirect
        );

        //--- Update pending transaction ---//
        $this->_oModule->_oDb->updateOrderPending($iPendingId, array(
            'order' => $oSubscription->id,
            'error_code' => $aResult['code'],
            'error_msg' => _t($aResult['message'])
        ));

        return $aResult;
    }

    public function getPortal($sCustomerId, $sSubscriptionId)
    {
    	$oPortal = false;

    	try {
    		ChargeBee_Environment::configure($this->_getSite(), $this->_getApiKey());
    		$oResult = ChargeBee_PortalSession::create(array(
				'customer' => array(
					'id' => $sCustomerId
				)));

    		$oPortal = $oResult->portalSession();
    	}
    	catch (Exception $oException) {
    		$iError = $oException->getCode();
    		$sError = $oException->getMessage();

    		$this->log('Get Portal Error: ' . $sError . '(' . $iError . ')');

    		return false;
    	}

    	return $oPortal;
    }

    public function getJsCode($aParams = array())
    {
    	$sSite = '';
    	bx_alert($this->_oModule->_oConfig->getName(), $this->_sName . '_get_js_code', 0, 0, array(
	    	'site' => &$sSite,
	    	'params' => &$aParams
    	));

    	return $this->_oModule->_oTemplate->getJsCode($this->_sName, array_merge(array(
			'sProvider' => $this->_sName,
			'sSite' => !empty($sSite) ? $sSite : $this->_getSite()
    	), $aParams));
    }

    /**
     * Single time payments aren't available with Chargebee
     */
    public function getButtonSingle($iClientId, $iVendorId, $aParams = array())
    {
        return '';
    }

    public function getButtonSingleJs($iClientId, $iVendorId, $aParams = array())
    {
        return array();
    }

    public function getButtonRecurring($iClientId, $iVendorId, $aParams = array())
    {
        return $this->_getButton(BX_PAYMENT_TYPE_RECURRING, $iClientId, $iVendorId, $aParams);
    }

    public function getButtonRecurringJs($iClientId, $iVendorId, $aParams = array())
    {
        return $this->_getButtonJs(BX_PAYMENT_TYPE_RECURRING, $iClientId, $iVendorId, $aParams);
    }

    protected function _getButton($sType, $iClientId, $iVendorId, $aParams = array())
    {
        list($sJsCode, $sJsMethod) = $this->_getButtonJs($sType, $iClientId, $iVendorId, $aParams);        

        return $this->_oModule->_oTemplate->parseHtmlByName('cbee_v3_button_' . $sType . '.html', array(
            'type' => $sType,
            'link' => 'javascript:void(0)',
            'caption' => _t($this->_sLangsPrefix . 'cbee_txt_checkout_with_' . $sType, $this->_sCaption),
            'onclick' => $sJsMethod,
            'js_object' => $this->_oModule->_oConfig->getJsObject($this->_sName),
            'js_code' => $sJsCode
        ));
    }
    
    protected function _getButtonJs($sType, $iClientId, $iVendorId, $aParams = array())
    {
        $sJsObject = $this->_oModule->_oConfig->getJsObject($this->_sName);

        $sSite = '';
        bx_alert($this->_oModule->_oConfig->getName(), $this->_sName . '_get_button', 0, $iClientId, array(
            'type' => &$sType, 
            'site' => &$sSite,
            'params' => &$aParams
        ));

        $sJsMethod = '';
        switch($sType) {
            case BX_PAYMENT_TYPE_SINGLE:
                /**
                 * Single time payments aren't available with Chargebee.
                 */
                break;

            case BX_PAYMENT_TYPE_RECURRING:
                $sJsMethod = $sJsObject . '.subscribe(this)';
                break;
        }

        return array($this->_oModule->_oTemplate->getJsCode($this->_sName, array_merge(array(
            'sProvider' => $this->_sName,
            'sSite' => !empty($sSite) ? $sSite : $this->_getSite(),
            'iClientId' => $iClientId
        ), $aParams)), $sJsMethod);
    }

    public function getMenuItemsActionsRecurring($iClientId, $iVendorId, $aParams = array())
    {
        if(empty($aParams['id']))
            return array();

        $sPrefix = 'bx-payment-strp-';
        $sJsObject = $this->_oModule->_oConfig->getJsObject($this->_sName);

        return array(
            array('id' => $sPrefix . 'manager', 'name' => $sPrefix . 'manager', 'class' => '', 'link' => 'javascript:void(0)', 'onclick' => "javascript:return " . $sJsObject . ".manage(this, '" . $aParams['id'] . "')", 'target' => '_self', 'title' => _t('_bx_payment_cbee_menu_item_title_manager'))
        );
    }
}

/** @} */
