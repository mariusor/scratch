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
/* @var \vsc\application\sitemaps\RwSiteMap $this */

//$this->getCurrentModuleMap()->addStyle('/s/css/capitals.css');
//$this->getCurrentModuleMap()->addStyle('/s/css/screen.css');

$oMap = $this->map ('', LOCAL_RES_PATH . 'littr/config/map.php');

// 404 controller
$oMap = $this->map ('(.+)\Z', \vsc\application\processors\ErrorProcessor::class);
$oMap->setTemplatePath(VSC_RES_PATH . 'templates');
$oMap->setTemplate ('404.php');
