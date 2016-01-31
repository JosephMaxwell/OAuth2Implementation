<?php
/**
 * SwiftOtter_Base is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SwiftOtter_Base is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with SwiftOtter_Base. If not, see <http://www.gnu.org/licenses/>.
 *
 * Copyright: 2013 (c) SwiftOtter Studios
 *
 * @author Joseph Maxwell
 * @copyright Swift Otter Studios, 1/30/16
 * @package default
 **/
namespace Controllers;

use GuzzleHttp\Client;
use Helpers\App;
use League\OAuth2\Client\Provider\Google;
use Models\Config;
use Models\Files;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Container;
use Slim\Views\Twig;

class LibraryController
{
    const AREA = 'library';

    /** @var App $this->helper */
    protected $helper;
    /** @var Config $this->config */
    protected $config;
    /** @var Twig $this->view */
    protected $view;

    public function __construct(Container $container)
    {
        $this->helper = $container->helper;
        $this->config = $container->config;
        $this->view = $container->view;

        $this->provider = new Google([
            'clientId' => $this->config->getClientId(),
            'clientSecret' => $this->config->getClientSecret(),
            'redirectUri' => $this->helper->getCallbackUrl(self::AREA)
        ]);
    }

    public function index(Request $request, Response $response)
    {
        $helper = $this->helper;

        if ($helper->hasAccessToken()) {
            $client = new \GuzzleHttp\Client();

            $fileResponse = $client->get('https://www.googleapis.com/drive/v2/files', [
                'headers' => $helper->getAuthorizedHeaders()
            ]);

            $files = new Files($fileResponse->getBody());
            return $this->view->render($response, 'files.twig', ['files' => $files->formatData(), 'area' => self::AREA]);
        } else {
            return $response->withStatus(301)->withHeader('Location', '/' . self::AREA . '/auth');
        }
    }

    public function authenticate(Request $request, Response $response)
    {
        session_start();
        $config = $this->config;

        $url = $this->provider->getAuthorizationUrl(['scope' => $config::SCOPE]);
        $_SESSION['oauth2_state'] = $this->provider->getState();

        return $response->withStatus(301)->withHeader('Location', $url);
    }

    public function authenticateCallback (Request $request, Response $response)
    {
        $code = $request->getQueryParams()['code'] ?? '';

        try {
            if (!$code) {
                throw new \Exception('No access code provided.');
            }

            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            $this->helper->setAccessToken([
                'access_token' => $token->getToken(),
                'expires' => $token->getExpires()
            ]);

            return $response->withStatus(301)->withHeader('Location', '/' . self::AREA);
        } catch (\Exception $ex) {
            return $this->view->render($response, 'error.twig', ['error' => $ex->getMessage()]);
        }
    }

    public function logout(Request $request, Response $response)
    {
        $this->helper->unsetAccessToken();

        return $response->withStatus(301)->withHeader('Location', '/' . self::AREA . '/auth');
    }
}