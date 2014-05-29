<?php
namespace Causal\Routing\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Xavier Perseguers <xavier@causal.ch>, Causal Sàrl
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Routing controller.
 *
 * @package     routing
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   2014 Causal Sàrl
 * @license     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RoutingController {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\ExtensionService
	 */
	protected $extensionService;

	/**
	 * @var array
	 */
	protected $routes;

	/**
	 * @var string
	 */
	protected $lastRouteName = NULL;

	/**
	 * Default contructor.
	 */
	public function __construct() {
		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->extensionService = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Service\\ExtensionService');
	}

	/**
	 * Dispatches the request and returns data.
	 *
	 * @return mixed
	 * @throws \RuntimeException
	 */
	public function dispatch() {
		$response = NULL;
		$route = GeneralUtility::_GET('route');
		$httpMethod = $_SERVER['REQUEST_METHOD'];

		if (preg_match('#^([^/]+)/(.*)$#', $route, $matches)) {
			$extensionKey = $matches[1];
			$subroute = $matches[2];

			if (ExtensionManagementUtility::isLoaded($extensionKey)) {
				$extensionPath = ExtensionManagementUtility::extPath($extensionKey);
				$routesFileName = $extensionPath . 'Configuration/Routes.yaml';
				if (@is_file($routesFileName)) {
					$this->loadRoutes($routesFileName);

					$controllerParameters = NULL;
					foreach ($this->routes as $route) {
						if (is_array($route['httpMethods'])) {
							if (!in_array($httpMethod, $route['httpMethods'])) {
								// Skip this route as it does not match the expected HTTP method (GET, HEAD, POST, PUT)
								continue;
							}
						}
						if (preg_match($route['uriPattern'], $subroute, $arguments)) {
							$this->lastRouteName = !empty($route['name']) ? sprintf('[%s] %s', $extensionKey, $route['name']) : NULL;
							$controllerParameters = $route['defaults'];
							$pluginParameters = array();

							foreach ($arguments as $key => $value) {
								if (!is_int($key)) {
									$key = str_replace('__AT__', '@', $key);
									if ($key{0} === '@') {
										$controllerParameters[$key] = $value;
									} else {
										$pluginParameters[$key] = $value;
									}
								}
							}

							$namespaceParts = explode('.', $controllerParameters['@package']);
							if (count($namespaceParts) === 2) {
								$controllerParameters['@vendor'] = $namespaceParts[0];
								$controllerParameters['@extension'] = GeneralUtility::underscoredToUpperCamelCase($namespaceParts[1]);
							} else {
								$controllerParameters['@extension'] = GeneralUtility::underscoredToUpperCamelCase($namespaceParts[0]);
							}
							if (empty($pluginParameters['action']) && !empty($controllerParameters['@action'])) {
								$pluginParameters['action'] = $controllerParameters['@action'];
							}
							if (empty($pluginParameters['format']) && !empty($controllerParameters['@format'])) {
								$pluginParameters['format'] = $controllerParameters['@format'];
							}

							if (!empty($controllerParameters['@plugin']) && (count($pluginParameters) > 0 || count($_POST) > 0)) {
								$pluginNamespace = $this->extensionService->getPluginNamespace($controllerParameters['@extension'], $controllerParameters['@plugin']);
								$postKeys = array_keys($_POST);
								foreach ($postKeys as $key) {
									$_POST[$pluginNamespace][$key] = $_POST[$key];
									unset($_POST[$key]);
								}
								foreach ($pluginParameters as $key => $value) {
									// TODO: should we put to $_POST under some conditions?
									$_GET[$pluginNamespace][$key] = $value;
								}
							}

							break;
						}
					}

					if ($controllerParameters !== NULL) {
						$this->initTSFE();

						/** @var \TYPO3\CMS\Extbase\Core\Bootstrap $bootstrap */
						$bootstrap = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Core\\Bootstrap');

						$configuration = array(
							'pluginName' => $controllerParameters['@plugin'],
							'extensionName' => $controllerParameters['@extension'],
						);
						if (!empty($controllerParameters['@vendor'])) {
							$configuration['vendorName'] = $controllerParameters['@vendor'];
						}

						$response = $bootstrap->run('', $configuration);
					}
				}
			}
		}

		return $response;
	}

	/**
	 * Returns the last route name.
	 *
	 * @return string
	 */
	public function getLastRouteName() {
		return $this->lastRouteName;
	}

	/**
	 * Loads routes from a given YAML file.
	 *
	 * @param string $yamlFileName
	 * @return void
	 */
	protected function loadRoutes($yamlFileName) {
		if (function_exists('yaml_parse')) {
			$contents = file_get_contents($yamlFileName);
			$routes = yaml_parse($contents);
		} else {
			require_once(__DIR__ . '/../Library/Spyc/Spyc.php');
			$routes = \Spyc::YAMLLoad($yamlFileName);
		}

		$this->routes = array();
		foreach ($routes as $route) {
			// Convert the URI pattern to a regular expression
			$route['uriPattern'] = str_replace('.', '\\.', $route['uriPattern']);
			$route['uriPattern'] = '#^' .
				preg_replace_callback(
					'/{([^}]+)}/',
					function ($m) {
						$name = str_replace('@', '__AT__', $m[1]);
						return '(?P<' . $name . '>[^/]+)';
					},
					$route['uriPattern']
				) .
				'#';

			$this->routes[] = $route;
		}
	}

	/**
	 * Initializes TSFE and sets $GLOBALS['TSFE'].
	 *
	 * @return void
	 */
	protected function initTSFE() {
		$pageId = GeneralUtility::_GP('id');
		/** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfe */
		$tsfe = GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
			$GLOBALS['TYPO3_CONF_VARS'],
			$pageId,
			''
		);

		\TYPO3\CMS\Frontend\Utility\EidUtility::initLanguage();
		\TYPO3\CMS\Frontend\Utility\EidUtility::initTCA();

		$tsfe->initFEuser();
		// We do not want (nor need) EXT:realurl to be invoked:
		//$tsfe->checkAlternativeIdMethods();
		$tsfe->determineId();
		$tsfe->initTemplate();
		$tsfe->getConfigArray();
		if ($pageId > 0) {
			$tsfe->settingLanguage();
		}
		$tsfe->settingLocale();

		$GLOBALS['TSFE'] = $tsfe;

		// Get linkVars, absRefPrefix, etc
		//\TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit();
	}

}

/** @var \Causal\Routing\Controller\RoutingController $routing */
$routing = GeneralUtility::makeInstance('Causal\\Routing\\Controller\\RoutingController');

try {
	$ret = $routing->dispatch();
} catch (\Exception $e) {
	header('HTTP/1.1 500 Internal Server Error');
	echo 'Error ' . $e->getCode() . ': ' . $e->getMessage();
	exit;
}

if ($ret === NULL) {
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
echo $ret;
