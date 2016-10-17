<?php
defined('TYPO3_MODE') || die();

$boot = function ($_EXTKEY) {
    // Register routing service
    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/Classes/Controller/RoutingController.php';
};

$boot($_EXTKEY);
unset($boot);
