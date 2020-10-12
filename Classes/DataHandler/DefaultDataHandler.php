<?php

namespace Causal\Routing\DataHandler;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class DefaultDataHandler extends AbstractDataHandler
{
    public function getParsedBody()
    {
        $data = [];
        switch ($this->request->getMethod()) {
            case 'POST':
                $data = $_POST;
                break;
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                $bodyContent = $this->request->getBody()->getContents();
                $data = $this->parseFormData($bodyContent);
                break;
        }
        return $data;
    }

    /**
     * @param string $rawData
     * @return array|void
     */
    protected function parseFormData($rawData)
    {
        // Fetch content and determine boundary
        $boundary = substr($rawData, 0, strpos($rawData, "\r\n"));
        if (empty($boundary)) {
            return;
        }
        // Fetch each part
        $parts = array_slice(explode($boundary, $rawData), 1);
        $data = array();

        foreach ($parts as $part) {
            // If this is the last part, break
            if ($part == "--\r\n") {
                break;
            }
            // Separate content from headers
            $part = ltrim($part, "\r\n");
            list($rawHeaders, $body) = explode("\r\n\r\n", $part, 2);

            // Parse the headers list
            $rawHeaders = explode("\r\n", $rawHeaders);
            $headers = array();
            foreach ($rawHeaders as $header) {
                list($name, $value) = explode(':', $header);
                $headers[strtolower($name)] = ltrim($value, ' ');
            }

            // Parse the Content-Disposition to get the field name, etc.
            if (isset($headers['content-disposition'])) {
                $filename = null;
                $tmp_name = null;
                preg_match(
                    '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
                    $headers['content-disposition'],
                    $matches
                );
                $name = $matches[2];
                //Parse File
                if (isset($matches[4])) {
                    // TODO: Add Files
                    //$this->addFiles($matches, $body, $value);
                } else { //Parse Field
                    $params = $name . '=' . substr($body, 0, strlen($body) - 2);
                    $data = array_merge_recursive($data, GeneralUtility::explodeUrl2Array($params, 1));
                }
            }
        }
        return $data;
    }

    protected function addFiles($matches, $body, $value)
    {
        //if labeled the same as previous, skip
        if (isset($_FILES[$matches[2]])) {
            return;
        }
        //get filename
        $filename = $matches[4];

        //get tmp name
        $filename_parts = pathinfo($filename);
        $tmp_name = tempnam(ini_get('upload_tmp_dir'), $filename_parts['filename']);

        //populate $_FILES with information, size may be off in multibyte situation
        $_FILES[$matches[2]] = array(
            'error' => 0,
            'name' => $filename,
            'tmp_name' => $tmp_name,
            'size' => strlen($body),
            'type' => $value
        );
        //place in temporary directory
        file_put_contents($tmp_name, $body);
    }
}
