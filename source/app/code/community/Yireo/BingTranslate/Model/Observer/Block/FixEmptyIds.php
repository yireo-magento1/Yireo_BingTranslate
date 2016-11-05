<?php
/**
 * Yireo BingTranslate for Magento
 *
 * @package     Yireo_BingTranslate
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

/**
 * BingTranslate observer
 */
class Yireo_BingTranslate_Model_Observer_Block_FixEmptyIds
{
    /**
     * Listen to the event core_block_abstract_to_html_after
     *
     * @parameter Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function execute(Varien_Event_Observer $observer)
    {
        if ($observer->getEvent()->getBlock()->getNameInLayout() !== 'root') {
            return $this;
        }

        $transport = $observer->getEvent()->getTransport();
        $html = $transport->getHtml();

        if (preg_match_all('/\<input([^\>]+)\>/', $html, $matches)) {
            foreach ($matches[0] as $matchIndex => $match) {
                if (!strstr($match, 'input-text')) {
                    continue;
                }

                if (strstr($match, ' id=')) {
                    continue;
                }

                if (preg_match('/name=\"([^\"]+)/', $match, $nameMatch) == false) {
                    continue;
                }

                $inputId = md5($match) . '_' . $this->getIncrement();
                $inputNew = str_replace($nameMatch[0], $nameMatch[0] . '" id="' . $inputId, $match);

                $html = str_replace($match, $inputNew, $html);
            }
        }

        $transport->setHtml($html);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIncrement()
    {
        static $i;
        $i++;

        return $i;
    }
}