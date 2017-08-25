<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\Routing\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;

// Load the routing controller
require_once(__DIR__ . '/RoutingController.php');

/** @var RoutingController $routing */
$routing = GeneralUtility::makeInstance(RoutingController::class);

try {
    $ret = $routing->dispatch();
} catch (\Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Error ' . $e->getCode() . ': ' . $e->getMessage();
    exit;
}

if ($ret === null) {
    header('HTTP/1.0 404 Not Found');
    echo <<<HTML
<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL {$_SERVER['REQUEST_URI']} was not found on this server.</p>
<hr>
<address>Routing Service at {$_SERVER['SERVER_NAME']}</address>
</body></html>
HTML;
    exit();
}

// Debugging information
$routeName = $routing->getLastRouteName();
if (!empty($routeName)) {
    header('X-Causal-Routing-Route: ' . $routeName);
}

if (is_string($ret) && $ret === '') {
    header('HTTP/1.1 204 No Content');
}

echo $ret;
