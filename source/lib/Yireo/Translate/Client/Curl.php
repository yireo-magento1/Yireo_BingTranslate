<?php
/**
 * @package     Yireo Translation Library
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2017 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

namespace Yireo\Translate\Client;

/**
 * Curl class
 */
class Curl
{
    protected $url = '';

    protected $headers = [];

    protected $data = [];

    protected $requestType = '';

    /**
     * Curl constructor.
     *
     * @param $url
     * @param array $headers
     * @param array $data
     * @param string $requestType
     */
    public function __construct($url, $headers = [], $data = [], $requestType = 'GET')
    {
        $this->url = $url;
        $this->headers = $headers;
        $this->data = $data;
        $this->requestType = $requestType;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function call()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($this->requestType == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (is_array($this->data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
            }
        }

        $response = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        if ($curlErrno) {
            $curlError = curl_error($ch);
            throw new \Exception($curlError);
        }

        $curlInfo = curl_getinfo($ch);
        if (isset($curlInfo['http_code']) && $curlInfo['http_code'] !== 200) {
            throw new \Exception(sprintf('HTTP status "%s"; CURL dump: %s; Response: %s', $curlInfo['http_code'], var_export($curlInfo, true), $response));
        }

        curl_close($ch);
        return $response;
    }
}