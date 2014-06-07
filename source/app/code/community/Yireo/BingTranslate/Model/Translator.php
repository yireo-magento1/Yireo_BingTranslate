<?php
/**
 * Yireo BingTranslate for Magento
 *
 * @package     Yireo_BingTranslate
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright (C) 2014 Yireo (http://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

/**
 * BingTranslate observer
 */
class Yireo_BingTranslate_Model_Translator extends Mage_Core_Model_Abstract
{
    protected $apiUrl = 'http://api.microsofttranslator.com/v2/Http.svc/Translate';

    protected $apiError = null;

    protected $apiTranslation = null;

    /**
     * Method to call upon the Bing API
     */
    public function translate($text = null, $fromLang = null, $toLang = null)
    {
        // Bork debugging
        if(Mage::getStoreConfig('catalog/bingtranslate/bork')) {
            $wordCount = (int)str_word_count($text);
            if($wordCount < 4) $wordCount = 4;
            $this->apiTranslation = str_repeat('bork ', $wordCount);
            return $this->apiTranslation;
        }

        // Demo
        $clientId = Mage::helper('bingtranslate')->getClientId();
        $clientSecret = Mage::helper('bingtranslate')->getClientSecret();
        if(strtolower($clientId) == 'demo' || strtolower($clientSecret) == 'demo') {
            $this->apiError = $this->__('API-translation is disabled for this demo');
            return false;
        }

        // Load some variables
        if(empty($text)) $text = $this->getData('text');
        if(empty($fromLang)) $fromLang = $this->getData('from');
        if(empty($toLang)) $toLang = $this->getData('toLang');
        if($toLang == 'auto') $toLang = null;
        
        // Skip empty to-language
        if(empty($toLang)) {
            $this->apiError = $this->__('Empty or unsupported destination-language');
            return false;
        }

        $headers = array();

        // Bing API fields
        $fields = array(
            'text' => $text,
            'to' => $toLang,
            'from' => $fromLang,
            'contentType' => 'text/html',
        );

        $url = $this->apiUrl;
        $url .= '?'.http_build_query($fields);

        // Add extra Authorization-header
        $accessToken = $this->getAccessToken();
        if(!empty($accessToken)) {
            $headers[] = 'Authorization: Bearer '.$accessToken;
        } else {
            return false;
        }

        // Make the CURL-call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Magento/PHP');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if(!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);

        // Detect an empty CURL response
        if(empty($result)) {
            $this->apiError = $this->__('Empty response');
            $this->apiError .= '['.$this->__('from %s to %s', $fromLang, $toLang).']';
            return false;
        }

        // Detect non-XML feedback
        if(!preg_match('/^</', $result)) {
            $this->apiError = $this->__('Not an XML response');
            $this->apiError .= '['.$this->__('from %s to %s', $fromLang, $toLang).']';
            return false;
        }

        // Detect HTML feedback
        if(preg_match('/\<\/html\>$/', $result)) {

            // Try to extract the message from this HTML
            if(preg_match('/<p>Message:(.*)<\/p>/m', $result, $match)) {
                $this->apiError = $this->__('HTML response: %s', trim(strip_tags($match[1])));
                return false;
            }

            if(!is_dir(BP.DS.'var'.DS.'log')) @mkdir(BP.DS.'var'.DS.'log');
            $tmp_file = BP.DS.'var'.DS.'log'.DS.'bingtranslate.log';
            $tmp_string = $this->__('Translating from %s to %s', $fromLang, $toLang);
            file_put_contents($tmp_file, $tmp_string."\n", FILE_APPEND);
            file_put_contents($tmp_file, $result."\n", FILE_APPEND);

            $this->apiError = $this->__('Response is HTML, not XML [saved in %s]', 'var/log/bingtranslate.log');
            return false;
        }

        // Parse the XML-data
        $xml = new SimpleXMLElement($result);
        if(is_object($xml)) {
            $translation = trim((string)$xml);

            // A valid translation was found
            if(!empty($translation)) {

                // Detect whether the translation was the same or not
                if($translation == $text) {
                    $this->apiError = $this->__('Translation resulted in same text');
                    $this->apiError .= '['.$this->__('from %s to %s', $fromLang, $toLang).']';
                    return false;

                // Send the translation
                } else {
                    $this->apiTranslation = $translation;
                    return $this->apiTranslation;
                }

                // The translation returned empty
            } else {
                $this->apiError = $this->__('Empty translation');
                $this->apiError .= '['.$this->__('from %s to %s', $fromLang, $toLang).']';
                return false;
            }
        }

        $this->apiError = $this->__('Unknown data');
        $this->apiError .= '['.$this->__('from %s to %s', $fromLang, $toLang).']';
        $this->apiError .= var_export($result, true);
        return false;
    }

    /**
     * Method to get the access token
     */
    protected function getAccessToken()
    {
        // Use the token in the cookie if available
        if(!empty($_COOKIE['bingtranslate_token'])) {
            return $_COOKIE['bingtranslate_token'];
        }

        // If client_id and client_secret empty, return nothing
        $clientId = Mage::helper('bingtranslate')->getClientId();
        $clientSecret = Mage::helper('bingtranslate')->getClientSecret();

        if(empty($clientId) || empty($clientSecret)) return null;

        // Windows Azure OAuth URL
        $url = 'https://datamarket.accesscontrol.windows.net/v2/OAuth2-13/';

        // Bing API fields
        $fields = array(
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'http://api.microsofttranslator.com/',
            'grant_type' => 'client_credentials',
        );

        // Make the CURL-call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        $result = curl_exec($ch);

        // Parse the JSON-data
        $data = json_decode($result);

        if (!empty($data->error)) {
            $error = $this->__('Error while requesting OAuth token: %s', $data->error);
            if (!empty($data->error_description)) $error .= ' ['.$data->error_description.']';
            $this->apiError = $error;
            return false;
        }

        if (!empty($data->access_token)) {
            if (headers_sent() == false) setcookie('bingtranslate_token', $data->access_token, time() + (60*5));
            return $data->access_token;
        }

        $this->apiError = $this->__('No access-token');
        return false;
    }

    public function hasApiError()
    {
        if(!empty($this->apiError)) {
            return true;
        }
        return false;
    }

    public function getApiError()
    {
        return $this->apiError;
    }

    public function getApiTranslation()
    {
        return $this->apiTranslation;
    }

    public function __($string, $variable1 = null, $variable2 = null)
    {
        if(is_array($string)) $string = explode('; ', $string);
        return Mage::helper('bingtranslate')->__($string, $variable1, $variable2);
    }
}
