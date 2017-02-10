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
     */
    protected function setTokenInSession($token)
    {
        if ($this->useSession()) {
            $_SESSION[$this->getSubscriptionKey() . '.token'] = $token;
            $_SESSION[$this->getSubscriptionKey() . '.time'] = time();
        }
    }

    /**
     * @return string
     */
    protected function getTokenFromSession()
    {
        if ($this->useSession() == false) {
            return '';
        }

        if (empty($_SESSION[$this->getSubscriptionKey() . '.token']) || empty($_SESSION[$this->getSubscriptionKey() . '.time'])) {
            return '';
        }

        $tokenTime = (int) $_SESSION[$this->getSubscriptionKey() . '.time'];
        $graceTime = 8 * 60;
        if ($tokenTime + $graceTime < time()) {
            $this->setTokenInSession('');
            return '';
        }

        return $_SESSION[$this->getSubscriptionKey() . '.token'];
    }

    /**
     * Check whether we are able to store anything in the session
     *
     * @return bool
     */
    protected function useSession()
    {
        // Check by flag
        if (!isset($this->params['session']) || $this->params['session'] != true) {
            return false;
        }

        // Check if the session has actually started
        if (session_status() == PHP_SESSION_NONE) {
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
     */
    public function toString()
    {
        return (string)$this->getToken();
    }
}