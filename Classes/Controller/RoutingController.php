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

use Causal\Routing\DataHandler\DataHandlerFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Frontend\Utility\EidUtility;

/**
 * Routing controller.
 *
 * @package     routing
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   2014-2017 Causal Sàrl
 * @license     https://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RoutingController
{
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
    protected $lastRouteName = null;

    /**
     * Default contructor.
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->extensionService = $this->objectManager->get(\TYPO3\CMS\Extbase\Service\ExtensionService::class);
    }

    /**
     * Dispatches the request and returns data.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function dispatch()
    {
        $controllerParameters = null;
        $response = null;
        $route = GeneralUtility::_GET('route');

        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['routing']['globalRoutes'])) {
            $this->routes = [];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['routing']['globalRoutes'] as $routesFileName) {
                if (substr($routesFileName, 0, 4) === 'EXT:') {
                    list($extensionKey, $fileName) = explode('/', substr($routesFileName, 4), 2);
                    $extensionPath = ExtensionManagementUtility::extPath($extensionKey);
                    $routesFileName = $extensionPath . $fileName;
                }
                if (@is_file($routesFileName)) {
                    $this->loadRoutes($routesFileName);
                }
            }
            if (count($this->routes) > 0) {
                $controllerParameters = $this->getControllerParameters($route);
            }
        }

        if ($controllerParameters === null) {
            $this->routes = [];
            if (preg_match('#^([^/]+)/(.*)$#', $route, $matches)) {
                $extensionKey = $matches[1];
                $subroute = $matches[2];

                if (ExtensionManagementUtility::isLoaded($extensionKey)) {
                    $extensionPath = ExtensionManagementUtility::extPath($extensionKey);
                    $routesFileName = $extensionPath . 'Configuration/Routes.yaml';
                    $routesFileNameAlternate = $extensionPath . 'Configuration/Routes.yml';
                    if (@is_file($routesFileName)) {
                        $this->loadRoutes($routesFileName);
                        $controllerParameters = $this->getControllerParameters($subroute, $extensionKey);
                    } elseif (@is_file($routesFileNameAlternate)) {
                        $this->loadRoutes($routesFileNameAlternate);
                        $controllerParameters = $this->getControllerParameters($subroute, $extensionKey);
                    }
                }
            }
        }

        if ($controllerParameters !== null) {
            $this->initTSFE();

            /** @var \TYPO3\CMS\Extbase\Core\Bootstrap $bootstrap */
            $bootstrap = $this->objectManager->get(\TYPO3\CMS\Extbase\Core\Bootstrap::class);

            $configuration = [
                'pluginName' => $controllerParameters['@plugin'],
                'extensionName' => $controllerParameters['@extension'],
            ];
            if (!empty($controllerParameters['@vendor'])) {
                $configuration['vendorName'] = $controllerParameters['@vendor'];
            }

            $response = $bootstrap->run('', $configuration);

            // Render headers from FE controller
            $GLOBALS['TSFE']->processOutput();
        }

        return $response;
    }

    /**
     * Returns the last route name.
     *
     * @return string
     */
    public function getLastRouteName()
    {
        return $this->lastRouteName;
    }

    /**
     * Returns the controller parameters and updates superglobal variables $_GET,
     * $_POST and $_FILES if needed.
     *
     * @param string $subroute
     * @param string $extensionKey
     * @return array|NULL
     */
    protected function getControllerParameters($subroute, $extensionKey = null)
    {
        $controllerParameters = null;
        $request = \TYPO3\CMS\Core\Http\ServerRequestFactory::fromGlobals();
        $httpMethod = $request->getMethod();

        foreach ($this->routes as $route) {
            if (is_array($route['httpMethods'])) {
                if (!in_array($httpMethod, $route['httpMethods'])) {
                    // Skip this route as it does not match the expected HTTP method (GET, HEAD, POST, PUT)
                    continue;
                }
            }
            if (preg_match($route['uriPattern'], $subroute, $arguments)) {
                $this->lastRouteName = !empty($route['name'])
                    ? sprintf('[%s] %s', ($extensionKey ?: 'GLOBAL'), $route['name'])
                    : null;
                $controllerParameters = $route['defaults'];
                $pluginParameters = [];

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
                    $controllerParameters['@extension'] = $namespaceParts[1];
                } else {
                    $extension = $namespaceParts[0];
                    $controllerParameters['@extension'] = $extension;
                }

                if (ucfirst($controllerParameters['@extension']) !== $controllerParameters['@extension']) {
                    $controllerParameters['@extension'] = GeneralUtility::underscoredToUpperCamelCase($controllerParameters['@extension']);
                }

                if (empty($pluginParameters['action']) && !empty($controllerParameters['@action'])) {
                    $pluginParameters['action'] = $controllerParameters['@action'];
                }
                if (empty($pluginParameters['format']) && !empty($controllerParameters['@format'])) {
                    $pluginParameters['format'] = $controllerParameters['@format'];
                }

                if (!empty($controllerParameters['@plugin'])) {
                    $pluginNamespace = $this->extensionService->getPluginNamespace(
                        $controllerParameters['@extension'],
                        $controllerParameters['@plugin']
                    );

                    $bodyData = $this->objectManager->get(DataHandlerFactory::class)->getParsedBody($request);

                    $this->tangleFilesArray($pluginNamespace);

                    if (!empty($controllerParameters['@controller'])) {
                        if (ucfirst($controllerParameters['@controller']) !== $controllerParameters['@controller']) {
                            $controllerParameters['@controller'] = GeneralUtility::underscoredToUpperCamelCase($controllerParameters['@controller']);
                        }
                        switch ($httpMethod) {
                            case 'DELETE':
                            case 'GET':
                            case 'PATCH':
                            case 'PUT':
                                $pluginParameters['controller'] = $controllerParameters['@controller'];
                                break;
                            case 'POST':
                                $bodyData['controller'] = $controllerParameters['@controller'];
                                break;
                        }
                    }

                    $postKeys = array_keys($bodyData);
                    foreach ($postKeys as $key) {
                        $_POST[$pluginNamespace][$key] = $bodyData[$key];
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

        return $controllerParameters;
    }

    /**
     * Transforms the _FILES superglobal into a more convoluted form to
     * be handled by Extbase.
     *
     * IMPORTANT: Your form should not contain any namespace.
     *
     * Correct:
     *   <input type="file" name="myfile">
     *   <input type="file" name="myfiles[]" multiple>
     *
     * Incorrect:
     *   <input type="file" name="mynamespace[myfile]">
     *   <input type="file" name="mynamespace[myfiles][]" multiple>
     *
     * @param string $namespace
     * @return void
     * @see \TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder::untangleFilesArray()
     */
    protected function tangleFilesArray($namespace)
    {
        if (!count($_FILES)) {
            return;
        }
        $files = array_keys($_FILES);
        $fileKeys = ['error', 'name', 'size', 'tmp_name', 'type'];
        $namespacedFiles = [];
        foreach ($files as $file) {
            $currentFile = $_FILES[$file];
            foreach ($fileKeys as $key) {
                $namespacedFiles[$namespace][$key][$file] = $currentFile[$key];
            }
        }
        $_FILES = $namespacedFiles;
    }

    /**
     * Loads routes from a given YAML file.
     *
     * @param string $yamlFileName
     * @return void
     */
    protected function loadRoutes($yamlFileName)
    {
        if (function_exists('yaml_parse')) {
            $contents = file_get_contents($yamlFileName);
            $routes = yaml_parse($contents);
        } else {
            require_once(__DIR__ . '/../Library/Spyc/Spyc.php');
            $routes = \Spyc::YAMLLoad($yamlFileName);
        }

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
    protected function initTSFE()
    {
        $pageId = GeneralUtility::_GP('id');
        /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfe */
        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
            \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class,
            $GLOBALS['TYPO3_CONF_VARS'],
            $pageId,
            ''
        );

        EidUtility::initLanguage();
        EidUtility::initTCA();

        $GLOBALS['TSFE']->initFEuser();
        // We do not want (nor need) EXT:realurl to be invoked:
        //$GLOBALS['TSFE']->checkAlternativeIdMethods();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->getConfigArray();
        if ($pageId > 0) {
            $GLOBALS['TSFE']->settingLanguage();
        }
        $GLOBALS['TSFE']->settingLocale();

        // Get linkVars, absRefPrefix, etc
        //\TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit();
    }
}
