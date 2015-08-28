<?php
namespace Helpers;

use Models\Files;

class App
{
    const SCOPE = 'https://www.googleapis.com/auth/drive.readonly';
    const COOKIE = 'auth';

    /**
     * Determines the current host of the application
     *
     * @return string
     */
    public static function getUrl()
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
    public static function getCookie()
    {
        $app = \Slim\Slim::getInstance();

        if ($cookie = $app->getCookie(self::COOKIE)) {
            $json = json_decode($cookie, true);

            if (is_array($json)) {
                return $json;
            }
        }

        return array();
    }

    /**
     * Loads the access token from the cookie
     *
     * @return string
     */
    public static function getAccessToken()
    {
        $cookie = self::getCookie();

        if (isset($cookie['access_token'])) {
            return $cookie['access_token'];
        } else {
            return '';
        }
    }

    /**
     * Determines whether the user is authorized to access application
     */
    public static function isAuthorized()
    {
        $cookie = self::getCookie();

        if (!isset($cookie['expires']) || $cookie['expires'] <= time() || !isset($cookie['access_token'])) {
            $app = \Slim\Slim::getInstance();
            $app->redirect('/app/auth');

            return false;
        }

        return true;
    }

    /**
     * Saves the access token to a cookie
     *
     * @param array $json
     * @return bool
     */
    public static function setAccessToken(array $json)
    {
        $app = \Slim\Slim::getInstance();

        if (isset($json['access_token']) && isset($json['expires_in'])) {
            $json['expires'] = $json['expires_in'] + time();
        }

        $app->setCookie(self::COOKIE, json_encode($json));

        return true;
    }
}