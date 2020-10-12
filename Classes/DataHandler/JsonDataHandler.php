<?php

namespace Causal\Routing\DataHandler;

class JsonDataHandler extends AbstractDataHandler
{
    public function getParsedBody()
    {
        $bodyContent = $this->request->getBody()->getContents();
        return json_decode($bodyContent, 1) ?: [];
    }
}
