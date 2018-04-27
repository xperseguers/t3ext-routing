<?php

namespace Causal\Routing\DataHandler;

use Causal\Routing\DataHandler\AbstractDataHandler;
use Causal\Routing\DataHandler\DefaultDataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandlerFactory
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * Default contructor.
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
    }

    /**
     * @param ServerRequest $request
     * @return array
     */
    public function getParsedBody(ServerRequest $request)
    {
        $dataHandler = $this->getDataHandler($request);
        return $dataHandler->getParsedBody();
    }

    /**
     * @param ServerRequest $request
     * @return DefaultDataHandler|object
     */
    protected function getDataHandler(ServerRequest $request)
    {
        $mimeType = $request->getHeaderLine('content-type');
        if ($dataHandler = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['routing']['dataHandler'][$mimeType]) {
            return $this->objectManager->get($dataHandler);
        }
        return $this->objectManager->get(DefaultDataHandler::class, $request);
    }
}
