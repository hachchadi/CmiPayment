<?php

namespace Hachchadi\CmiPayment;

use Illuminate\Support\Facades\Http;
use Hachchadi\CmiPayment\Exceptions\InvalidConfiguration;
use Hachchadi\CmiPayment\Exceptions\InvalidRequest;

class CmiClient
{
    protected $config;

    public function __construct()
    {
        $this->config = config('cmi');
        $this->validateConfig();
    }

    protected function validateConfig()
    {
        $requiredConfigKeys = [
            'base_uri', 'client_id', 'store_key', 'ok_url', 'fail_url', 'shop_url', 'callback_url'
        ];

        foreach ($requiredConfigKeys as $key) {
            if (empty($this->config[$key])) {
                throw InvalidConfiguration::attributeNotSpecified($key);
            }
        }
    }

    public function processPayment(array $data)
    {
        $data['clientid'] = $this->config['client_id'];
        $data['storekey'] = $this->config['store_key'];
        $data['okurl'] = $this->config['ok_url'];
        $data['failurl'] = $this->config['fail_url'];
        $data['callbackurl'] = $this->config['callback_url'];
        $data['rnd'] = microtime();
        $data['hash'] = $this->generateHash($data);

        $response = Http::asForm()->post($this->config['base_uri'], $data);
        return $response->json();
    }

    protected function generateHash(array $data): string
    {
        $plainText = implode('|', array_values($data)) . '|' . $this->config['store_key'];
        return base64_encode(hash($this->config['hash_algorithm'], $plainText, true));
    }
}
