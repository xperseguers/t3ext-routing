<?php

namespace Causal\Routing\DataHandler;

use TYPO3\CMS\Core\Http\ServerRequest;

abstract class AbstractDataHandler
{
    /**
     * @var ServerRequest
     */
    protected $request;

    public function __construct(ServerRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Retrieves the session token.
     *
     * @return array
     */
    abstract public function getParsedBody();
}
