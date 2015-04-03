<?php
/**
 * Copyright (c) BoonEx Pty Limited - http://www.boonex.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    TridentCore Trident Core
 * @{
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

bx_import('BxDolLanguages');

function getPageMainCode()
{
    $oTemplate = BxDolTemplate::getInstance();    

    $bEnabled = getParam('sys_site_cover_enabled');
    if(!$bEnabled)
    	$oTemplate->displayPageNotFound();

    $oTemplate->addJs(array('skrollr/skrollr.min.js'));
    $oTemplate->addCss(array('splash.css'));
    return $oTemplate->parseHtmlByName('splash.html', array(
    	'join_link' => BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=create-account'),
    	'login_form' => BxDolService::call('system', 'login_form', array(), 'TemplServiceLogin')
    )); //getParam('sys_site_cover_code');
}

check_logged();

$oTemplate = BxDolTemplate::getInstance();
$oTemplate->setPageNameIndex(BX_PAGE_DEFAULT);
$oTemplate->setPageContent ('page_main_code', getPageMainCode());
$oTemplate->getPageCode();

/** @} */
