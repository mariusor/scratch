<?php
/**
 * This file is included in the vscSiteMap::load () function
 * It should be used to load other sitemaps or point to specific controllers
 *
 * the less specific regexes should be posted at the top
 *
 * @author marius orcsik <marius@habarnam.ro>
 * @date 09.08.30
 */
/* @var $this vscRwSiteMap */

//$this->getCurrentModuleMap()->addStyle('/s/css/capitals.css');
//$this->getCurrentModuleMap()->addStyle('/s/css/screen.css');

// loading the vsc default map - handles main controllers
//$this->map ('.*', VSC_RES_PATH . 'application/map.php');

// this will break if the current map is the first loaded
$this->getCurrentModuleMap()->setMainTemplatePath(VSC_RES_PATH . 'templates');
$this->getCurrentModuleMap()->setMainTemplate('main.php');

$oMap = $this->map ('', LOCAL_RES_PATH . 'htlm/config/map.php');

// 404 controller
$oMap = $this->map ('(.+)\Z', VSC_RES_PATH . 'application/processors/vscerrorprocessor.class.php');
$oMap->setTemplatePath(VSC_RES_PATH . 'templates');
$oMap->setTemplate ('404.php');
