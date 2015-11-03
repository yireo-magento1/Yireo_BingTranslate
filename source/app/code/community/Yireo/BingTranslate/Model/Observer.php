<?php
/**
 * Yireo BingTranslate for Magento
 *
 * @package     Yireo_BingTranslate
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

/**
 * BingTranslate observer
 */
class Yireo_BingTranslate_Model_Observer extends Yireo_BingTranslate_Model_Observer_Abstract
{
    /**
     * Listen to the event core_block_abstract_to_html_before
     *
     * @parameter Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function coreBlockAbstractToHtmlBefore($observer)
    {
        // Check if this event can continue
        if ($this->allow($observer) == false) {
            return $this;
        }

        // Get the variables
        $block = $observer->getEvent()->getBlock();
        $element = $block->getElement();

        // Determine the data-type
        $data_type = $this->getDataType();

        // Fetch the languages from the configuration
        $from_language = Mage::helper('bingtranslate')->getFromLanguage();
        $to_language = Mage::helper('bingtranslate')->getToLanguage();

        // Construct the button-label
        $button_label = Mage::helper('bingtranslate')->getButtonLabel();

        // Determine whether this field is disabled or not
        $disabled = false;
        if ($from_language == $to_language) {
            $disabled = true;
        }

        // Fetch the data ID (either category ID or product ID) from the URL
        $data_id = Mage::app()->getRequest()->getParam('id');
        if (empty($data_id)) {
            $data_id = Mage::app()->getRequest()->getParam('page_id');
        }

        if (empty($data_id)) {
            $data_id = Mage::app()->getRequest()->getParam('block_id');
        }

        // If this data-type is unknown, do not display anything
        if ($data_type == 'unknown') {
            return $this;
        }

        // If this is a Root Catalog, do not display anything
        if ($data_type == 'category') {
            $category = Mage::getModel('catalog/category')->load($data_id);
            if ($category->getParentId() == 1) {
                return $this;
            }
        }

        // Fetch the store ID from the URL
        $store_id = Mage::app()->getRequest()->getParam('store');

        // Determine the HTML ID
        $html_id = $element->getHtmlId();

        // Determine the attribute-code
        $attribute_code = $element->getData('name');

        // Construct the button-arguments
        $buttonArgs = array($data_id, $attribute_code, $html_id, $store_id, $from_language, $to_language);
        $buttonHtml = Mage::helper('bingtranslate/observer')->button($attribute_code, $button_label, $disabled, $buttonArgs);

        // Append all constructed HTML-code to the existing HTML-code
        $html = $element->getData('after_element_html');
        $html .= $buttonHtml;
        $element->setData('after_element_html', $html);

        // Construct the JavaScript
        $jsHtml = Mage::helper('bingtranslate/observer')->script($attribute_code, $html_id);

        // Insert the JavaScript in the bottom of the page
        $layout = Mage::app()->getFrontController()->getAction()->getLayout();
        $jsBlock = $layout->createBlock('core/text');
        $jsBlock->setText($jsHtml);
        $layout->getBlock('before_body_end')->insert($jsBlock);

        return $this;
    }

    /**
     * Method fired on the event <controller_action_predispatch>
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Yireo_BingTranslate_Model_Observer
     */
    public function controllerActionPredispatch($observer)
    {
        // Run the feed
        Mage::getModel('bingtranslate/feed')->updateIfAllowed();
    }

    /**
     * Method fired on the event <content_translate_after>
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Yireo_BingTranslate_Model_Observer
     */
    public function contentTranslateAfter($observer)
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
     * @return $this|array
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

    /**
     * Method fired on the event <core_block_abstract_prepare_layout_before>
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Yireo_BingTranslate_Model_Observer
     */
    public function coreBlockAbstractPrepareLayoutBefore($observer)
    {
        $block = $observer->getEvent()->getBlock();
        $blockClass = 'Mage_Adminhtml_Block_Widget_Grid_Massaction';

        if ($block instanceof $blockClass && $block->getRequest()->getControllerName() == 'catalog_product') {
            $block->addItem('bingtranslate', array(
                'label' => 'Translate via BingTranslate',
                'url' => Mage::helper('adminhtml')->getUrl('adminhtml/bingtranslate/batch', array('type' => 'product')),
            ));
        }
    }
}
