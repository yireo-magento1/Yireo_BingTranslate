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
 * Bork class
 */
class Bork implements HandlerInterface
{
    /**
     * @var string
     */
    protected $text = '';

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
        $text = $this->text;
        $textBlocks = preg_split('/(%[^ ]+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $newTextBlocks = array();

        $searchMap = array(
            '/au/', '/\Bu/', '/\Btion/', '/an/', '/a\B/', '/en\b/',
            '/\Bew/', '/\Bf/', '/\Bir/', '/\Bi/', '/\bo/', '/ow/', '/ph/',
            '/th\b/', '/\bU/', '/y\b/', '/v/', '/w/', '/oo/', '/oe/'
        );

        $replaceMap = array(
            'oo', 'oo', 'shun', 'un', 'e', 'ee',
            'oo', 'ff', 'ur', 'ee', 'oo', 'oo', 'f',
            't', 'Oo', 'ai', 'f', 'v', 'ø', 'œ',
        );

        foreach ($textBlocks as $text) {
            if (strlen($text) && $text[0] == '%') {
                $newTextBlocks[] = (string)$text;
                continue;
            }

            $orgtext = $text;
            $text = preg_replace($searchMap, $replaceMap, $text);

            if ($orgtext == $text && count($newTextBlocks)) {
                $text .= '-a';
            }

            if (empty($text)) {
                $text = $orgText;
            }

            $newTextBlocks[] = (string)$text;
        }

        $text = implode('', $newTextBlocks);
        $text = preg_replace('/([:.?!])(.*)/', '\\2\\1', $text);

        return $text;
    }
}