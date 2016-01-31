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
use Models\Config;
use Models\Files;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Container;
use Slim\Views\Twig;

class ManualController
{
    const AREA = 'manual';

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
    }

    /**
     * Sends request:
     *     curl -H "Authorization: Bearer 123456asdfghjkl_token"
     *          -H "Referer: [current_url]"
     *             https://www.googleapis.com/drive/v2/files
     *
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface|static
     * @throws \Exception
     */
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

    /**
     * Redirecting to: https://accounts.google.com/o/oauth2/auth?
     *                                   response_type=code
     *                                  &redirect_uri=[callback_address]
     *                                  &scope=https://www.googleapis.com/auth/drive.readonly
     *                                  &client_id=[client_id]
     *                                  &access_type=online
     *                                  &include_granted_scopes=true
     *
     * @param Request $request
     * @param Response $response
     * @return mixed
     */

    public function authenticate(Request $request, Response $response)
    {
        $responseUrl = $this->helper->getCallbackUrl(self::AREA);
        $config = $this->config; // dereference for PHPStorm bug

        $params = [
            'response_type'          => 'code',
            'redirect_uri'           => $responseUrl,
            'scope'                  => $config::SCOPE,
            'client_id'              => $this->config->getClientId(),
            'access_type'            => 'online',
            'include_granted_scopes' => 'true'
        ];

        $url = $this->config->getAuthUri() . '?' . http_build_query($params);
        return $response->withStatus(301)->withHeader('Location', $url);
    }

    /**
     * Sends request:
     *      curl --data "code=[GET->code]
     *                   &grant_type=authorization_code
     *                   &client_id=[client_id]
     *                   &client_secret=[client_secret]
     *                   &redirect_uri=[url_of_this_function]"
     *            https://www.googleapis.com/oauth2/v3/token
     *
     * Gets response:
     * [
     *      'code' => '4/PTIwf4eUb3ajdD21_access_code'
     *      'scope' => 'https://www.googleapis.com/auth/drive.readonly'
     * ]
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\MessageInterface|\Psr\Http\Message\ResponseInterface
     */
    public function authenticateCallback (Request $request, Response $response)
    {
        $code = $request->getQueryParams()['code'] ?? '';

        try {
            if (!$code) {
                throw new \Exception('No access code provided.');
            }

            $client = new Client();

            $params = [
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'client_id'     => $this->config->getClientId(),
                'client_secret' => $this->config->getClientSecret(),
                'redirect_uri'  => $this->helper->getCallbackUrl(self::AREA)
            ];

            $response = $client->post($this->config->getTokenUri(), [
                'form_params' => $params
            ]);

            $json = json_decode($response->getBody(), true);
            $this->helper->setAccessToken($json);

            if (!isset($json['access_token'])) {
                throw new \Exception("Access token not returned.");
            }

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