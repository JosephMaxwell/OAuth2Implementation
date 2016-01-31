<?php

namespace Models;

class Config
{
    protected $loaded = false;
    protected $data = array();

    const CONTEXT = 'web';
    const CLIENT_SECRETS_FILE = 'client_secrets.json';
    const SCOPE = 'https://www.googleapis.com/auth/drive.readonly';


    protected function getData()
    {
        if (!$this->loaded) {
            $this->load(self::CONTEXT);
        }

        return $this->data;
    }

    protected function load($context)
    {
        if (file_exists('client_secrets.json')) {
            $contents = json_decode(file_get_contents(self::CLIENT_SECRETS_FILE), true);

            if (isset($contents[$context])) {
                $this->data = $contents[$context];
            }
        } else {
            throw new \Exception('Please go to Google Developers Console, get your client ID and secret, and put it in the home directory (not very secure, I know) as client_secrets.json');
        }
    }

    public function getFirstRedirectUri()
    {
        $uriList = $this->getByKey('redirect_uris');
        if (is_array($uriList)) {
            return array_shift($uriList);
        }
    }

    public function getClientId()
    {
        return $this->getByKey('client_id');
    }

    public function getAuthUri()
    {
        return $this->getByKey('auth_uri');
    }

    public function getTokenUri()
    {
        return $this->getByKey('token_uri');
    }

    public function getCertUrl()
    {
        return $this->getByKey('auth_provider_x509_cert_url');
    }

    public function getClientSecret()
    {
        return $this->getByKey('client_secret', true);
    }

    protected function getByKey($key, $graceful = false)
    {
        $data = $this->getData();

        if (isset($data[$key])) {
            return $data[$key];
        } else {
            if (!$graceful) {
                throw new \Exception("$key was not set in " . self::CLIENT_SECRETS_FILE);
            }
        }
    }
}