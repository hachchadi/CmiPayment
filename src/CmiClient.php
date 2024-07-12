<?php

namespace Hachchadi\CmiPayment;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Hachchadi\CmiPayment\Exceptions\InvalidConfiguration;
use Hachchadi\CmiPayment\Exceptions\InvalidRequest;
use Hachchadi\CmiPayment\Exceptions\InvalidResponseHash;

class CmiClient
{

    private string $baseUri;
    private string $clientId;
    private string $storeKey;
    private string $storeType;
    private string $tranType;
    private string $lang;
    private string $currency;
    private string $okUrl;
    private string $failUrl;
    private string $shopUrl;
    private string $callbackUrl;
    private bool $callbackResponse;
    private string $hashAlgorithm;
    private string $encoding;
    private bool $autoRedirect;
    private string $sessionTimeout;
    private string $rnd;
    private string $amount;
    private string $oid;
    private string $email;
    private string $billToName;
    private string $tel;
    private bool $currenciesList;
    private string $amountCur;
    private string $symbolCur;
    private string $description;
    private string $hash;

    private string $baseUriApi;
    private array $apiCredentials;

    public function __construct()
    {
        $this->baseUri = config('cmi.baseUri');
        $this->baseUriApi = config('cmi.baseUriApi');
        $this->clientId = config('cmi.clientId');
        $this->storeKey = config('cmi.storeKey');
        $this->storeType = config('cmi.storeType');
        $this->tranType = config('cmi.tranType');
        $this->lang = config('cmi.lang');
        $this->currency = config('cmi.currency');
        $this->okUrl = config('cmi.okUrl');
        $this->failUrl = config('cmi.failUrl');
        $this->shopUrl = config('cmi.shopUrl');
        $this->callbackUrl = config('cmi.callbackUrl');
        $this->callbackResponse = (bool) config('cmi.callbackResponse');
        $this->hashAlgorithm = config('cmi.hashAlgorithm');
        $this->encoding = config('cmi.encoding');
        $this->autoRedirect = (bool) config('cmi.autoRedirect');
        $this->sessionTimeout = config('cmi.sessionTimeout');
        $this->rnd = microtime();
        $this->apiCredentials = config('cmi.apiCredentials'); // Fetch API credentials from config
        $this->guardAgainstInvalidConfiguration();
    }

    public function processPayment(array $data)
    {
        // Validate and merge the necessary data
        $data = $this->getCmiData($data);

        // Generate the hash
        $data['HASH'] = $this->generateHash($data);

        $this->guardAgainstInvalidRequest($data); // Validate input data before processing

        // Generate the HTML form
        $this->generateHtmlForm($data);
    }

    public function getCmiStatus(string $orderId): string
    {
        try {
            $url = $this->baseUriApi;
            $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <CC5Request>
                <Name>'.$this->apiCredentials['Name'].'</Name>
                <Password>'.$this->apiCredentials['Password'].'</Password>
                <ClientId>'.$this->apiCredentials['ClientId'].'</ClientId>
                <OrderId>'.$orderId.'</OrderId>
                <Extra>
                    <ORDERHISTORY>QUERY</ORDERHISTORY>
                </Extra>
            </CC5Request>';

            $client = new Client();
            $response = $client->request('POST', $url, [
                'body' => $xml,
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                ],
            ]);

            $responseBody = $response->getBody()->getContents();

            $encode_response = json_encode(simplexml_load_string($responseBody));
            $decode_response = json_decode($encode_response, TRUE);

            $status = $decode_response['Response'];

        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            $status = '';
        }

        return $status;
    }

    protected function generateHtmlForm(array $data): string
    {
        $html = "<html>";
        $html .= "<head>";
        $html .= "<meta http-equiv='Content-Language' content='tr'>";
        $html .= "<meta http-equiv='Content-Type' content='text/html; charset=ISO-8859-9'>";
        $html .= "<meta http-equiv='Pragma' content='no-cache'>";
        $html .= "<meta http-equiv='Expires' content='now'>";
        $html .= "</head>";
        $html .= "<body onload='document.forms[\"redirectpost\"].submit();'>";
        $html .= "<form name='redirectpost' method='post' action='{$this->baseUri}'>";

        foreach ($data as $key => $value) {
            $html .= "<input type='hidden' name='{$key}' value='" . trim($value) . "'>";
        }

        $html .= "</form>";
        $html .= "</body>";
        $html .= "</html>";

        // Output the HTML form and exit
        echo $html;
        exit();
    }

    public function generateHash(array $data): string
    {
        $this->unsetData($data);

        // Assign store key
        $storeKey = $this->storeKey;

        // Retrieve and sort parameters
        $cmiParams = $data;
        $postParams = array_keys($cmiParams);
        natcasesort($postParams);

        // Construct hash input string
        $hashval = '';
        foreach ($postParams as $param) {
            $paramValue = trim($cmiParams[$param]);
            $escapedParamValue = str_replace("|", "\\|", str_replace("\\", "\\\\", $paramValue));
            $lowerParam = strtolower($param);
            if ($lowerParam != "hash" && $lowerParam != "encoding") {
                $hashval .= $escapedParamValue . "|";
            }
        }

        // Append storeKey and prepare for hashing
        $escapedStoreKey = str_replace("|", "\\|", str_replace("\\", "\\\\", $storeKey));
        $hashval .= $escapedStoreKey;

        // Calculate hash
        $calculatedHashValue = hash('sha512', $hashval);
        $hash = base64_encode(pack('H*', $calculatedHashValue));

        // Store hash in requireOpts for further use
        $this->hash = $hash;

        return $hash;
    }

    public function validateHash(array $data): bool
    {
        $this->unsetData($data);

        $storeKey = $this->storeKey;
        $hashval = '';
        foreach ($data as $key => $value) {
            if ($key !== 'HASH' && $key !== 'encoding') {
                $escapedValue = str_replace("|", "\\|", str_replace("\\", "\\\\", $value));
                $hashval .= $escapedValue . "|";
            }
        }
        $escapedStoreKey = str_replace("|", "\\|", str_replace("\\", "\\\\", $storeKey));
        $hashval .= $escapedStoreKey;
        $calculatedHash = base64_encode(pack('H*', hash('sha512', $hashval)));

        if ($calculatedHash !== $data['HASH']) {
            throw InvalidResponseHash::hashMismatch();
        }

        return true;
    }

    public function getCmiData(array $params = []): array
    {
        $cmiParams = array_merge(get_object_vars($this), $params);
        $this->unsetData($cmiParams);

        return $cmiParams;
    }

    private function unsetData(&$data): void
    {
        unset($data['storeKey'], $data['baseUri'],$data['baseUriApi'],$data['apiCredentials']);
    }

    private function guardAgainstInvalidConfiguration()
    {
        // clientId
        if (! $this->clientId) {
            throw InvalidConfiguration::clientIdNotSpecified();
        }

        if (preg_match('/\s/', $this->clientId)) {
            throw InvalidConfiguration::clientIdInvalid();
        }

        // storeKey
        if (! $this->storeKey) {
            throw InvalidConfiguration::storeKeyNotSpecified();
        }

        if (preg_match('/\s/', $this->storeKey)) {
            throw InvalidConfiguration::storeKeyInvalid();
        }

        // storeType
        if (! $this->storeType) {
            throw InvalidConfiguration::attributeNotSpecified('merchant payment model (storeType)');
        }

        if (preg_match('/\s/', $this->storeType)) {
            throw InvalidConfiguration::attributeInvalidString('merchant payment model (storeType)');
        }

        // tranType
        if (! $this->tranType) {
            throw InvalidConfiguration::attributeNotSpecified('transaction type (tranType)');
        }

        if (preg_match('/\s/', $this->tranType)) {
            throw InvalidConfiguration::attributeInvalidString('transaction type (tranType)');
        }

        // lang
        if (! in_array($this->lang, ['fr', 'ar', 'en'])) {
            throw InvalidConfiguration::langValueInvalid();
        }

        // baseUri
        if (! $this->baseUri) {
            throw InvalidConfiguration::attributeNotSpecified('payment gateway (baseUri)');
        }

        if (preg_match('/\s/', $this->baseUri)) {
            throw InvalidConfiguration::attributeInvalidString('payment gateway (baseUri)');
        }

        if (! preg_match("/\b(?:(?:https):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $this->baseUri)) {
            throw InvalidConfiguration::attributeInvalidUrl('payment gateway (baseUri)');
        }

        // okUrl
        if (! $this->okUrl) {
            throw InvalidConfiguration::attributeNotSpecified('okUrl');
        }

        if (preg_match('/\s/', $this->okUrl)) {
            throw InvalidConfiguration::attributeInvalidString('okUrl');
        }

        if (! preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $this->okUrl)) {
            throw InvalidConfiguration::attributeInvalidUrl('okUrl');
        }

        // failUrl
        if (! $this->failUrl) {
            throw InvalidConfiguration::attributeNotSpecified('failUrl');
        }

        if (preg_match('/\s/', $this->failUrl)) {
            throw InvalidConfiguration::attributeInvalidString('failUrl');
        }

        if (! preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $this->failUrl)) {
            throw InvalidConfiguration::attributeInvalidUrl('failUrl');
        }

        // shopUrl
        if (! $this->shopUrl) {
            throw InvalidConfiguration::attributeNotSpecified('shopUrl');
        }

        if (preg_match('/\s/', $this->shopUrl)) {
            throw InvalidConfiguration::attributeInvalidString('shopUrl');
        }

        if (! preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $this->shopUrl)) {
            throw InvalidConfiguration::attributeInvalidUrl('shopUrl');
        }

        // callbackUrl
        if (! $this->callbackUrl) {
            throw InvalidConfiguration::attributeNotSpecified('callbackUrl');
        }

        if (preg_match('/\s/', $this->callbackUrl)) {
            throw InvalidConfiguration::attributeInvalidString('callbackUrl');
        }

        if (! preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $this->callbackUrl)) {
            throw InvalidConfiguration::attributeInvalidUrl('callbackUrl');
        }

        // hashAlgorithm
        if (! $this->hashAlgorithm) {
            throw InvalidConfiguration::attributeNotSpecified('hash version (hashAlgorithm)');
        }

        if (preg_match('/\s/', $this->hashAlgorithm)) {
            throw InvalidConfiguration::attributeInvalidString('hash version (hashAlgorithm)');
        }

        // encoding
        if (! $this->encoding) {
            throw InvalidConfiguration::attributeNotSpecified('data encoding (encoding)');
        }

        if (preg_match('/\s/', $this->encoding)) {
            throw InvalidConfiguration::attributeInvalidString('data encoding (encoding)');
        }

        // sessionTimeout
        if (! $this->sessionTimeout) {
            throw InvalidConfiguration::attributeNotSpecified('session timeout (sessionTimeout)');
        }

        if ((int) $this->sessionTimeout < 30 || (int) $this->sessionTimeout > 2700) {
            throw InvalidConfiguration::sessionimeoutValueInvalid();
        }
    }

    public function guardAgainstInvalidRequest($data)
    {
        // amount
        if ($data['amount'] === null) {
            throw InvalidRequest::amountNotSpecified();
        }

        if (! preg_match('/^\d+(\.\d{2})?$/', $data['amount'])) {
            throw InvalidRequest::amountValueInvalid();
        }

        // currency
        if ($data['currency'] === null) {
            throw InvalidRequest::currencyNotSpecified();
        }

        if (! is_string($data['currency']) || strlen($data['currency']) != 3) {
            throw InvalidRequest::currencyValueInvalid();
        }

        // oid
        if ($data['oid'] === null) {
            throw InvalidRequest::attributeNotSpecified('order ID (oid)');
        }

        if (! is_string($data['oid']) || preg_match('/\s/', $data['oid'])) {
            throw InvalidRequest::attributeInvalidString('order ID (oid)');
        }

        // email
        if ($data['email'] === null) {
            throw InvalidRequest::attributeNotSpecified('customer email (email)');
        }

        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw InvalidRequest::emailValueInvalid();
        }

        // billToName
        if ($data['billToName'] === null) {
            throw InvalidRequest::attributeNotSpecified('customer name (billToName)');
        }

        if (! is_string($data['billToName']) || $data['billToName'] === '') {
            throw InvalidRequest::attributeInvalidString('customer name (billToName)');
        }

        // tel
        if (isset($data['tel']) && ! is_string($data['tel'])) {
            throw InvalidRequest::attributeInvalidString('customer phone (tel)');
        }

        // amountCur
        if (isset($data['amountCur']) && ! is_string($data['amountCur'])) {
            throw InvalidRequest::attributeInvalidString('conversion amount (amountCur)');
        }

        // symbolCur
        if (isset($data['symbolCur']) && ! is_string($data['symbolCur'])) {
            throw InvalidRequest::attributeInvalidString('conversion currency symbol (symbolCur)');
        }

        // description
        if (isset($data['description']) && ! is_string($data['description'])) {
            throw InvalidRequest::attributeInvalidString('description');
        }
    }
}
