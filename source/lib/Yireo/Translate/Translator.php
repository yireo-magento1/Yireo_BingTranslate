<?php
/**
 * @package     Yireo Translation Library
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2017 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

namespace Yireo\Translate;

use InvalidArgumentException;
use Yireo\Translate\Exception\EmptyText;
use Yireo\Translate\Exception\UnknownLanguage;
use Yireo\Translate\Api\HandlerInterface;

/**
 * Translator class
 */
class Translator
{
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
    public function __construct($text = '', $toLanguage = '', $fromLanguage = '', $params = [])
    {
        $this->text = $text;
        $this->toLanguage = $toLanguage;
        $this->fromLanguage = $fromLanguage;
        $this->params = $params;
    }

    /**
     * Translate a given
     *
     * @throws EmptyText
     * @throws \Yireo\Translate\Exception\UnknownLanguage
     * @throws \InvalidArgumentException
     * @return string
     */
    public function translate()
    {
        if (empty($this->text)) {
            throw new EmptyText('No text to translate');
        }

        if (empty($this->toLanguage)) {
            throw new UnknownLanguage('No destination language set');
        }

        if (empty($this->params['handler'])) {
            throw new InvalidArgumentException('Empty translation handler');
        }

        $handlerClass = (string) $this->params['handler'];

        /** @var HandlerInterface $handler */
        $handler = new $handlerClass($this->text, $this->toLanguage, $this->fromLanguage, $this->params);
        $translatedText = $handler->translate();
        $this->setTranslatedText($translatedText);

        return $this->getTranslatedText();
    }

    /**
     * @param string $translatedText
     */
    protected function setTranslatedText($translatedText)
    {
        $this->translatedText = $translatedText;
    }

    /**
     * @throws \Yireo\Translate\Exception\SameTranslation
     * @return string
     */
    public function getTranslatedText()
    {
        if ($this->text === $this->translatedText) {
            throw new \Yireo\Translate\Exception\SameTranslation('Translation is same as origin');
        }

        return $this->translatedText;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @param string $fromLanguage
     */
    public function setFromLanguage($fromLanguage)
    {
        $this->fromLanguage = $fromLanguage;
    }

    /**
     * @param string $toLanguage
     */
    public function setToLanguage($toLanguage)
    {
        $this->toLanguage = $toLanguage;
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }
}