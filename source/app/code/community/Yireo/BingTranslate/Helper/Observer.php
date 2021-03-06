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
 * BingTranslate helper
 */
class Yireo_BingTranslate_Helper_Observer extends Mage_Core_Helper_Abstract
{
    /**
     * Helper method to fetch the button-HTML
     *
     * @param int $id
     * @param string $label
     * @param bool $disabled
     * @param array $arguments
     * @return string
     */
    public function button($id, $label, $disabled = false, $arguments = array())
    {
        // Convert the button-arguments into a JavaScript-ready array
        $jsArgs = array();
        foreach ($arguments as $argument) {
            $jsArgs[] = '\'' . $argument . '\'';
        }

        // Construct the button HTML-code
        $html = Mage::getSingleton('core/layout')
            ->createBlock('adminhtml/widget_button', '', array(
                'label' => Mage::helper('bingtranslate')->__($label),
                'type' => 'button',
                'disabled' => $disabled,
                'class' => ($disabled) ? 'bingtranslate_button disabled' : 'bingtranslate_button',
                'style' => 'margin-right:5px;margin-top:5px;',
                'id' => 'bingtranslate_button_' . $id,
                'onclick' => 'YireoBingTranslate.translateAttribute(' . implode(',', $jsArgs) . ')'
            ))->toHtml();

        return $html;
    }

    /**
     * Helper method to fetch the button-HTML
     *
     * @param string $attribute_code
     * @param string $html_id
     * @return string
     */
    public function script($attribute_code, $html_id)
    {
        // Construct the button JavaScript-code
        $html = "<script type=\"text/javascript\">\n"
            . "Event.observe(window, 'load', function() {\n"
            . "    var button = $('bingtranslate_button_" . $attribute_code . "');\n"
            . "    var field = $('" . $html_id . "');\n"
            . "    if(field && field.disabled && button) {\n"
            . "        button.disabled = true;\n"
            . "        button.className = 'disabled';\n"
            . "    }\n"
            . "});\n"
            . "</script>\n";

        return $html;
    }
}
