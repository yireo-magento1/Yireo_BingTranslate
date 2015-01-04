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
class Yireo_BingTranslate_Model_Translator extends Mage_Core_Model_Abstract
{
    /**
     * String containing the API URL
     *
     * @var string
     */
    protected $apiUrl = 'http://api.microsofttranslator.com/v2/Http.svc/Translate';

    /**
     * Container for possible API errors
     *
     * @var null
     */
    protected $apiError = null;

    /**
     * String containing the translated content received from the API
     *
     * @var null
     */
    protected $apiTranslation = null;

    /**
     * Method to call upon the Bing API
     *
     * @param string $text
     * @param string $fromLang
     * @param string $toLang
     * @return text
     */
    public function translate($text = null, $fromLang = null, $toLang = null)
    {
        // Bork debugging
        if(Mage::getStoreConfig('catalog/bingtranslate/bork')) {
            $this->apiTranslation = $this->bork($text);
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
        if(preg_match('/\<\/html\>$/', $result) || preg_match('/\ style=\"/', $result)) {

            // Try to extract the message from this HTML
            if(preg_match('/<p>Message:(.*)<\/p>/m', $result, $match)) {
                $this->apiError = $this->__('HTML response: %s', trim(strip_tags($match[1])));
                return false;
            }

            $this->debugLog($result, $fromLang, $toLang);
            $this->apiError = $this->__('Response is HTML, not XML [saved in %s]', 'var/log/bingtranslate.log');
            return false;
        }

        // Fetch the XML-data
        try {
            $xml = new SimpleXMLElement($result);
        } catch(Exception $e) {

            $this->debugLog($e->getMessage(), $fromLang, $toLang);
            $this->apiError = $this->__('Response is HTML, not XML [saved in %s]', 'var/log/bingtranslate.log');
            return false;
        }

        // Parse the XML-data
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
        $cookieToken = Mage::getModel('core/cookie')->getData('bingtranslate_token');
        if(!empty($cookieToken)) {
            return $cookieToken;
        }

        // If client_id and client_secret empty, return nothing
        $clientId = Mage::helper('bingtranslate')->getClientId();
        $clientSecret = Mage::helper('bingtranslate')->getClientSecret();

        if(empty($clientId) || empty($clientSecret)) {
            return null;
        }

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
            if (headers_sent() == false) {
                Mage::getModel('core/cookie')->setData('bingtranslate_token', $data->access_token);
            }
            return $data->access_token;
        }

        $this->apiError = $this->__('No access-token');
        return false;
    }

    /**
     * Method to check whether there has been an error in the API
     *
     * @return bool
     */
    public function hasApiError()
    {
        if(!empty($this->apiError)) {
            return true;
        }
        return false;
    }

    /**
     * Method to return the API error, if any
     *
     * @return null
     */
    public function getApiError()
    {
        return $this->apiError;
    }

    /**
     * Method to return the API translation
     *
     * @return string
     */
    public function getApiTranslation()
    {
        return $this->apiTranslation;
    }

    /**
     * Method to translate a certain text
     *
     * @param $string
     * @param null $variable1
     * @param null $variable2
     * @return string
     */
    public function __($string, $variable1 = null, $variable2 = null)
    {
        if(is_array($string)) $string = explode('; ', $string);
        return Mage::helper('bingtranslate')->__($string, $variable1, $variable2);
    }

    /**
     * Method to write some debugging to a log
     *
     * @access public
     * @param $string
     * @param $fromLang
     * @param $toLang
     * @return void
     */
    public function debugLog($string, $fromLang, $toLang)
    {
        if(!is_dir(BP.DS.'var'.DS.'log')) @mkdir(BP.DS.'var'.DS.'log');
        $tmp_file = BP.DS.'var'.DS.'log'.DS.'bingtranslate.log';
        $tmp_string = $this->__('Translating from %s to %s', $fromLang, $toLang);
        file_put_contents($tmp_file, $tmp_string."\n", FILE_APPEND);
        file_put_contents($tmp_file, $string."\n", FILE_APPEND);
    }

    /**
     * Method to borkify a given text
     *
     * @access public
     * @param $text
     * @return mixed|string
     */
    public function bork($text)
    {
        $textBlocks = preg_split( '/(%[^ ]+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
        $newTextBlocks = array();
        foreach ( $textBlocks as $text )
        {
            if ( strlen( $text ) && $text[0] == '%' )
            {
                $newTextBlocks[] = (string) $text;
                continue;
            }
            $orgtext = $text;
            $searchMap = array(
                '/au/', '/\Bu/', '/\Btion/', '/an/', '/a\B/', '/en\b/',
                '/\Bew/', '/\Bf/', '/\Bir/', '/\Bi/', '/\bo/', '/ow/', '/ph/',
                '/th\b/', '/\bU/', '/y\b/', '/v/', '/w/', '/ooo/',
            );
            $replaceMap = array(
                'oo', 'oo', 'shun', 'un', 'e', 'ee',
                'oo', 'ff', 'ur', 'ee', 'oo', 'oo', 'f',
                't', 'Oo', 'ai', 'f', 'v', 'oo',
            );
            $text = preg_replace( $searchMap, $replaceMap, $text );
            if ( $orgtext == $text && count( $newTextBlocks ) )
            {
                $text .= '-a';
            }
            $newTextBlocks[] = (string) $text;
        }
        $text = implode( '', $newTextBlocks );
        $text = preg_replace( '/([:.?!])(.*)/', '\\2\\1', $text );
        $text .= ' Bork bork.';

        return $text;
    }
}
