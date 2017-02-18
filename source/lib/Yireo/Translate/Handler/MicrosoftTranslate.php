<?php
/**
 * @package     Yireo Translation Library
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2017 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

namespace Yireo\Translate\Handler;

use Yireo\Translate\Api\HandlerInterface;
use Yireo\Translate\Exception\InvalidHtml;
use Yireo\Translate\Exception\TooManyCharacters;
use Yireo\Translate\Exception\EmptyToken;
use Yireo\Translate\Client\Curl as CurlClient;

/**
 * MicrosoftTranslate class
 */
class MicrosoftTranslate implements HandlerInterface
{
    /**
     * API URL
     */
    const TRANSLATE_URL = 'https://api.microsofttranslator.com/v2/Http.svc/Translate';

    /**
     * API URL
     */
    const TRANSLATEARRAY_URL = 'https://api.microsofttranslator.com/v2/Http.svc/TranslateArray';

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
     * @throws InvalidHtml
     * @throws TooManyCharacters
     * @throws \Yireo\Translate\Exception\EmptyToken
     * @return string
     */
    public function translate()
    {
        $this->sanitizeText();

        if ($this->getContentType() == 'text/html' && !$this->isValidHtml($this->text)) {
            throw new InvalidHtml('Text does not contain valid HTML');
        }

        if (strlen($this->text) > 10000) {
            throw new TooManyCharacters('Text can not be more than 10.000 characters');
        }

        $token = new MicrosoftTranslate\Token($this->params);

        if (empty($token)) {
            throw new EmptyToken('Received empty token');
        }

        $bearerHeader = "Authorization: Bearer " . $token->toString();
        $headers = [$bearerHeader];
        $headers[] = 'Content-Type: text/xml';

        //return $this->_translateArray($headers);
        return $this->_translate($headers);
    }

    /**
     * @param $headers
     *
     * @return mixed|string
     */
    protected function _translate($headers)
    {
        $data = [];
        $data['to'] = $this->toLanguage;
        $data['from'] = $this->fromLanguage;
        $data['contentType'] = $this->getContentType();
        $data['category'] = $this->getCategory();
        $data['text'] = $this->text;

        $translateUrl = self::TRANSLATE_URL . '?' . http_build_query($data);
        $client = new CurlClient($translateUrl, $headers);

        $response = $client->call();

        return $this->getTranslationFromXml($response);
    }

    /**
     * Currently not working
     *
     * @param $headers
     *
     * @return mixed|string
     */
    protected function _translateArray($headers)
    {
        $this->text = strip_tags($this->text);
        $lines = explode('. ', $this->text);
        foreach($lines as $lineId => $line) {
            $lines[$lineId] = trim($line);
        }

        $data = [];
        $data['to'] = $this->toLanguage;
        $data['from'] = $this->fromLanguage;
        $data['texts'] = $lines;

        $translateUrl = self::TRANSLATEARRAY_URL;
        $client = new CurlClient($translateUrl, $headers, $data, 'POST');

        $response = $client->call();

        return $this->getTranslationFromXml($response);
    }

    /**
     * Sanitize text
     */
    protected function sanitizeText()
    {
        $this->text = trim($this->text);
        $chars = ["\n", "\r", 0xA, 0xB, 0xC, 0xD, 0x85, 0x2028, 0x2029];
        foreach ($chars as $char) {
            $this->text = str_replace($char, ' ', $this->text);
        }
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
        foreach ((array)$xml[0] as $val) {
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
            return (string)$this->params['content_type'];
        }

        if (strstr($this->text, '</') && strstr($this->text, '>')) {
            return 'text/html';
        }

        return 'text/plain';
    }

    /**
     * @param $string
     *
     * @return bool
     * @throws \Exception
     */
    protected function isValidHtml($string)
    {
        return true;

        $start = strpos($string, '<');
        $end = strrpos($string, '>', $start);
        $len = strlen($string);

        if ($end !== false) {
            $string = substr($string, $start);
        } else {
            $string = substr($string, $start, $len - $start);
        }

        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $string = html_entity_decode($string);
        $string = trim($string);
        $string = '<content>'.$string.'</content>';
        simplexml_load_string($string);

        foreach (libxml_get_errors() as $error) {
            throw new InvalidHtml($error->code . ': ' . trim($error->message) . ' [line ' . $error->line . ']');
        }

        return (bool)count(libxml_get_errors()) == 0;
    }

    /**
     * @return string
     */
    protected function getCategory()
    {
        if (isset($this->params['category'])) {
            return (string)$this->params['category'];
        }

        return 'general';
    }
}