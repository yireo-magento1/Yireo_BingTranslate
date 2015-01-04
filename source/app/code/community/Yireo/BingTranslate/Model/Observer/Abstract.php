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
class Yireo_BingTranslate_Model_Observer_Abstract
{
    /**
     * Method to check whether a certain event is allowed
     *
     * @access protected
     * @param $observer
     * @return bool
     */
    protected function allow($observer)
    {
        // If the configuration is told to disable this module, quit now
        if(Mage::helper('bingtranslate')->enabled() == false) {
            return false;
        }

        // Get the parameters from the event
        $transport = $observer->getEvent()->getTransport();
        $block = $observer->getEvent()->getBlock();
        if(empty($block) || !is_object($block)) {
            return false;
        }

        // Check whether this block-object is of the right instance
        $allowed_types = array(
            'adminhtml/catalog_form_renderer_fieldset_element',
            'adminhtml/widget_form_renderer_fieldset_element',
        );

        $allowed_blocks = array(
            'Mage_Adminhtml_Block_Catalog_Form_Renderer_Fieldset_Element',
            'Mage_Adminhtml_Block_Widget_Form_Renderer_Fieldset_Element',
        );

        if(!in_array(get_class($block), $allowed_blocks) && !in_array($block->getType(), $allowed_types)) {
            return false;
        }

        // Check if the form-element is text-input based
        $element = $block->getElement();
        $allowedElements = array('Varien_Data_Form_Element_Text', 'Varien_Data_Form_Element_Editor');
        if(!in_array(get_class($element), $allowedElements) && !stristr(get_class($element), 'wysiwyg')) {
            return false;
        }

        return true;
    }

    /**
     * Method to return the data types for specific URLs
     *
     * @access protected
     * @param null
     * @return string
     */
    protected function getDataType()
    {
        static $data_type = null;
        if(empty($data_type)) {
            $currentUrl = Mage::helper('core/url')->getCurrentUrl();

            if(stristr($currentUrl, 'cms_block/edit')) {
                $data_type = 'block';
            } elseif(stristr($currentUrl, 'cms_page/edit')) {
                $data_type = 'page';
            } elseif(stristr($currentUrl, 'catalog_category/edit')) {
                $data_type = 'category';
            } elseif(stristr($currentUrl, 'catalog_product/edit')) {
                $data_type = 'product';
            } else {
                $data_type = 'unknown';
            }
        }

        return $data_type;
    }
}
