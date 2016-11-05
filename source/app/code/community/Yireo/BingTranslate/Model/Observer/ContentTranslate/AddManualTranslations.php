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
class Yireo_BingTranslate_Model_Observer_ContentTranslate_AddManualTranslations
{
    /**
     * Method fired on the event <content_translate_after>
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Yireo_BingTranslate_Model_Observer_ContentTranslate_AddManualTranslations
     */
    public function execute($observer)
    {
        $text = $observer->getEvent()->getText();
        $fromLang = $observer->getEvent()->getFrom();
        $toLang = $observer->getEvent()->getTo();

        $translations = $this->getManualTranslations($fromLang, $toLang);

        if (!empty($translations)) {
            foreach ($translations as $translationFrom => $translationTo) {
                $text = str_replace($translationFrom, $translationTo, $text);
            }

            $observer->getEvent()->setData('text', $text);
        }

        return $this;
    }

    /**
     * Get the manual translations from a translation file
     *
     * @param string $fromLang
     * @param string $toLang
     *
     * @return array
     */
    protected function getManualTranslations($fromLang, $toLang)
    {
        $translations = array();
        $translationFile = $this->getManualTranslationsFile($fromLang, $toLang);

        if (empty($translationFile)) {
            return $translations;
        }

        if (($handle = fopen($translationFile, 'r')) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if (empty($data[0])) continue;
                if (empty($data[1])) continue;
                $translations[$data[0]] = $data[1];
            }
        }
        fclose($handle);

        return $translations;
    }

    /**
     * Determine the translation file
     *
     * @param $fromLang
     * @param $toLang
     *
     * @return string
     */
    protected function getManualTranslationsFile($fromLang, $toLang)
    {
        $translationFolder = Mage::getSingleton('core/design_package')->getBaseDir(
            array('_area' => 'adminhtml', '_type' => 'translations')
        );

        $translationFiles = array(
            'translate_' . $fromLang . '_' . $toLang . '.csv',
            'translate_' . $toLang . '.csv',
            $fromLang . '_' . $toLang . '.csv',
            $toLang . '.csv',
        );

        $translationFile = null;
        foreach ($translationFiles as $translationFile) {
            if (file_exists($translationFolder . '/' . $translationFile)) {
                $translationFile = $translationFolder . '/' . $translationFile;
                break;
            } else {
                $translationFile = null;
            }
        }

        return $translationFile;
    }
}