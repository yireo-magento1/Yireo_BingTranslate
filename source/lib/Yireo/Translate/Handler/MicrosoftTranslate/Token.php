<?php
/**
 * @package     Yireo Translation Library
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2017 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

namespace Yireo\Translate\Handler\MicrosoftTranslate;

use Yireo\Translate\Exception\InvalidSubscriptionKey;
use Yireo\Translate\Exception\EmptySubscriptionKey;

/**
 * Token class
 */
class Token
{
    /**
     * API URL
     */
    const URL = 'https://api.cognitive.microsoft.com/sts/v1.0/issueToken';

    /**
     * @var string
     */
    protected $token = '';

    /**
     * @var string
     */
    protected $params = '';

    /**
     * Token constructor.
     *
     * @param $params
     */
    public function __construct($params)
    {
        $this->params = $params;
        $this->session = \Mage::getModel('core/session');
    }

    /**
     * @throws \Yireo\Translate\Exception\EmptyToken
     * @return string
     */
    protected function getToken()
    {
        $this->token = $this->getTokenFromSession();
        if (!empty($this->token)) {
            return $this->token;
        }

        $authHeader = "Ocp-Apim-Subscription-Key: " . $this->getSubscriptionKey();
        $headers = [];
        $headers[] = $authHeader;
        $headers[] = 'Content-Type: text/json';
        $headers[] = 'Accept: application/json';

        $client = new \Yireo\Translate\Client\Curl(self::URL, $headers, null, 'POST');
        $response = $client->call();

        if (empty($response)) {
            throw new \Yireo\Translate\Exception\EmptyToken(sprintf('Empty token for key "%s"', $this->getSubscriptionKey()));
        }

        $this->setTokenInSession($response);

        return $response;
    }

    /**
     * @param $token
     * @return bool
     */
    protected function setTokenInSession($token)
    {
        if ($this->useSession() === false) {
            return false;
        }

        $this->session->setData($this->getSubscriptionKey() . '.token', $token);
        $this->session->setData($this->getSubscriptionKey() . '.time', time());
        return true;
    }

    /**
     * @return string
     */
    protected function getTokenFromSession()
    {
        if ($this->useSession() === false) {
            return '';
        }

        $tokenTime = (int)$this->session->getData($this->getSubscriptionKey() . '.time');
        $sessionToken = $this->session->getData($this->getSubscriptionKey() . '.token');

        if (empty($tokenTime)) {
            return '';
        }

        if (empty($sessionToken)) {
            return '';
        }

        $graceTime = 8 * 60;
        if ($tokenTime + $graceTime < time()) {
            $this->setTokenInSession('');
            return '';
        }

        return $sessionToken;
    }

    /**
     * Check whether we are able to store anything in the session
     *
     * @return bool
     */
    protected function useSession()
    {
        // Check by flag
        if (!isset($this->params['session']) || $this->params['session'] !== true) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     * @throws EmptySubscriptionKey
     * @throws InvalidSubscriptionKey
     */
    protected function getSubscriptionKey()
    {
        if (empty($this->params['key'])) {
            throw new EmptySubscriptionKey('Empty subscription key');
        }

        $key = strtolower($this->params['key']);

        if (!preg_match('/^([a-z0-9]{10,50})/', $key)) {
            throw new InvalidSubscriptionKey(sprintf('Invalid subscription key: %s', $key));
        }

        return $key;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function toString()
    {
        $token = $this->getToken();

        return $token;
    }
}