<?php
namespace Helpers;

use GuzzleHttp\Client;
use Models\Config;
use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Container;
use Slim\Http\Cookies;

class App
{
    const COOKIE = 'auth';

    /** @var Config $this->config */
    protected $config;

    /** @var Request $this->request */
    protected $request;

    /** @var Response $this->response */
    protected $response;

    /** @var Cookies */
    protected $cookie;

    public function __construct(Container $container)
    {
        $this->request = $container->request;
        $this->config = $container->config;
    }

    public function getAuthorizedHeaders()
    {
        $cookie = $this->getCookie();
        
        if (!isset($cookie['access_token'])) {
            throw new \Exception("Not authorized!");
        }
        
        return [
            'Authorization' => 'Bearer ' . $cookie['access_token'],
            'Referer' => $this->getUrl()
        ];
    }
  
    public function getCallbackUrl($area)
    {
        return $this->getUrl() . $area . '/callback';
    }

    /**
     * Determines the current host of the application
     *
     * @return string
     */
    public function getUrl()
    {
        $path = [];

        if ($_SERVER['HTTPS'] == 'on') {
            $path[] = 'https://';
        } else {
            $path[] = 'http://';
        }

        $path[] = $_SERVER['HTTP_HOST'] . '/';

        return implode($path);
    }

    /**
     * Retrieves and decodes the cookie
     *
     * @return array
     */
    public function getCookie()
    {
        $cookies = $this->request->getCookieParams();

        if (isset($cookies[self::COOKIE])) {
            $json = json_decode($cookies[self::COOKIE], true);

            if (is_array($json)) {
                return $json;
            }
        }

        return [];
    }

    /**
     * Loads the access token from the cookie
     *
     * @return string
     */
    public function getAccessToken()
    {
        $cookie = $this->getCookie();
        return $cookie['access_token'] ?? '';
    }

    public function unsetAccessToken()
    {
        setcookie(self::COOKIE, null);

        return $this;
    }

    /**
     * Determines whether the user is authorized to access application
     */
    public function hasAccessToken()
    {
        $cookie = $this->getCookie();

        return isset($cookie['expires']) && $cookie['expires'] >= time() && isset($cookie['access_token']);
    }

    /**
     * Saves the access token to a cookie
     *
     * @param array $json
     * @return bool
     */
    public function setAccessToken(array $json)
    {
        if (isset($json['access_token']) && isset($json['expires_in'])) {
            $json['expires'] = $json['expires_in'] + time();
        }

        setcookie(self::COOKIE, json_encode($json));

        return true;
    }
}