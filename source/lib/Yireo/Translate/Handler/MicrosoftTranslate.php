<?php
/**
 * @package     Yireo Translation Library
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2017 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

namespace Yireo\Translate\Handler;

use Yireo\Translate\Api\HandlerInterface;

/**
 * MicrosoftTranslate class
 */
class MicrosoftTranslate implements HandlerInterface
{
    /**
     * API URL
     */
    const URL = 'https://api.microsofttranslator.com/v2/Http.svc/Translate';

    /**
     * @var string
     */
    protected $text = '';

    /**
     * @var string
     */
    protected $translatedText = '';

    /**
     * @var string
     */
    protected $fromLanguage = '';

    /**
     * @var string
     */
    protected $toLanguage = '';

    /**
     * @var array
     */
    protected $params = [];

    /**
     * Translator constructor.
     *
     * @param string $text
     * @param string $toLanguage
     * @param string $fromLanguage
     * @param array $params
     */
    public function __construct($text, $toLanguage, $fromLanguage = '', $params = [])
    {
        $this->text = $text;
        $this->toLanguage = $toLanguage;
        $this->fromLanguage = $fromLanguage;
        $this->params = $params;

        if (empty($this->params)) {
            throw new \InvalidArgumentException('Empty parameters');
        }
    }

    /**
     * @return string
     */
    public function translate()
    {
        $token = new \Yireo\Translate\Handler\MicrosoftTranslate\Token($this->params);

        $bearerHeader = "Authorization: Bearer " . $token->toString();
        $headers = [];
        $headers[] = $bearerHeader;
        $headers[] = 'Content-Type: text/xml';

        $urlParams = [];
        $urlParams['text'] = urlencode($this->text);
        $urlParams['to'] = $this->toLanguage;
        $urlParams['from'] = $this->fromLanguage;
        $urlParams['contentType'] = $this->getContentType();
        $urlParams['category'] = $this->getCategory();

        $translateUrl = self::URL . '?' . http_build_query($urlParams);
        $client = new \Yireo\Translate\Client\Curl($translateUrl, $headers);
        $response = $client->call();

        return $this->getTranslationFromXml($response);
    }

    /**
     * @param $xmlString
     *
     * @return mixed|string
     */
    protected function getTranslationFromXml($xmlString)
    {
        $translation = '';
        $xml = simplexml_load_string($xmlString);
        foreach((array)$xml[0] as $val){
            $translation .= $val;
        }

        // @todo: Weird hacks that should not be here
        $translation = preg_replace('/\%([0-9]+)\ ([A-Z]{1})\ /', '%\1\2', $translation);
        $translation = str_replace('% ', '%', $translation);
        $translation = urldecode($translation);
        $translation = preg_replace('/([\ \t\n\r]+)/', ' ', $translation);
        $translation = str_replace(' , ', ', ', $translation);

        return $translation;
    }

    /**
     * @return string
     */
    protected function getContentType()
    {
        if (isset($this->params['content_type'])) {
            return (string) $this->params['content_type'];
        }

        if (strstr($this->text, '</') && strstr($this->text, '>')) {
            return 'text/html';
        }

        return 'text/plain';
    }

    /**
     * @return string
     */
    protected function getCategory()
    {
        if (isset($this->params['category'])) {
            return (string) $this->params['category'];
        }

        return 'general';
    }
}