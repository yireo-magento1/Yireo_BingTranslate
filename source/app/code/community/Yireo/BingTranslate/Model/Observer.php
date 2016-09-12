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
 *
 * @deprecated
 */
class Yireo_BingTranslate_Model_Observer
{
    /**
     * @param $observer
     *
     * @return $this
     * @deprecated
     */
    public function controllerActionPredispatch($observer)
    {
        return $this;
    }

    /**
     * @param $observer
     *
     * @return $this
     * @deprecated
     */
    public function coreBlockAbstractPrepareLayoutBefore($observer)
    {
        return $this;
    }

    /**
     * @param $observer
     *
     * @return $this
     * @deprecated
     */
    public function coreBlockAbstractToHtmlBefore($observer)
    {
        return $this;
    }
}
