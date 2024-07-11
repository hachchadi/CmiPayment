<?php

namespace Hachchadi\CmiPayment;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Hachchadi\CmiPayment\Exceptions\InvalidConfiguration;
use Hachchadi\CmiPayment\Exceptions\InvalidRequest;

class CmiClient
{
    private string $baseUri;
    private string $baseUriApi;
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
    private string $hash;
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
        $data = array_merge($data, [
            'clientid' => $this->clientId,
            'storekey' => $this->storeKey,
            'tranType' => $this->tranType,
            'currency' => $this->currency,
            'lang' => $this->lang,
            'hashAlgorithm' => $this->hashAlgorithm,
            'okurl' => $this->okUrl,
            'failurl' => $this->failUrl,
            'callbackurl' => $this->callbackUrl,
            'rnd' => microtime(),
            'autoRedirect' => $this->autoRedirect
        ]);

        // Generate the hash
        $data['hash'] = $this->generateHash($data);

        $this->guardAgainstInvalidRequest($data); // Validate input data before processing

        // URL for the CMI payment gateway
        $url = $this->baseUri;

        // Generate the HTML form
        $html = $this->generateHtmlForm($url, $data);

        // Output the HTML form and exit
        echo $html;
        exit();
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

    protected function generateHtmlForm(string $url, array $data): string
    {
        $html = "<html>";
        $html .= "<head>";
        $html .= "<meta http-equiv='Content-Language' content='tr'>";
        $html .= "<meta http-equiv='Content-Type' content='text/html; charset=ISO-8859-9'>";
        $html .= "<meta http-equiv='Pragma' content='no-cache'>";
        $html .= "<meta http-equiv='Expires' content='now'>";
        $html .= "</head>";
        $html .= "<body onload='document.forms[\"redirectpost\"].submit();'>";
        $html .= "<form name='redirectpost' method='post' action='{$url}'>";

        foreach ($data as $key => $value) {
            $html .= "<input type='hidden' name='{$key}' value='" . trim($value) . "'>";
        }

        $html .= "</form>";
        $html .= "</body>";
        $html .= "</html>";

        return $html;
    }

    public function generateHash($data): string
    {
        // Assign store key
        $storeKey = $this->storeKey;

        // Exclude 'storekey' from requireOpts
        unset($this->storeKey);

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

    private function guardAgainstInvalidConfiguration()
    {
        //clientId
        if (! $this->clientId) {
            throw InvalidConfiguration::clientIdNotSpecified();
        }

        if (preg_match('/\s/', $this->clientId)) {
            throw InvalidConfiguration::clientIdInvalid();
        }

        //storeKey
        if (! $this->storeKey) {
            throw InvalidConfiguration::storeKeyNotSpecified();
        }

        if (preg_match('/\s/', $this->storeKey)) {
            throw InvalidConfiguration::storeKeyInvalid();
        }

        //storeType
        if (! $this->storeType) {
            throw InvalidConfiguration::attributeNotSpecified('modèle du paiement du marchand (storeType)');
        }

        if (preg_match('/\s/', $this->storeType)) {
            throw InvalidConfiguration::attributeInvalidString('modèle du paiement du marchand (storeType)');
        }

        //tranType
        if (! $this->tranType) {
            throw InvalidConfiguration::attributeNotSpecified('Type de la transaction (tranType)');
        }

        if (preg_match('/\s/', $this->tranType)) {
            throw InvalidConfiguration::attributeInvalidString('Type de la transaction (tranType)');
        }

        //lang
        if (! in_array($this->lang, ['fr', 'ar', 'en'])) {
            throw InvalidConfiguration::langValueInvalid();
        }

        //baseUri
        if (! $this->baseUri) {
            throw InvalidConfiguration::attributeNotSpecified('gateway de paiement (baseUri)');
        }

        if (preg_match('/\s/', $this->baseUri)) {
            throw InvalidConfiguration::attributeInvalidString('gateway de paiement (baseUri)');
        }

        if (! preg_match("/\b(?:(?:https):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $this->baseUri)) {
            throw InvalidConfiguration::attributeInvalidUrl('gateway de paiement (baseUri)');
        }

        //okUrl
        if (! $this->okUrl) {
            throw InvalidConfiguration::attributeNotSpecified('okUrl');
        }

        if (preg_match('/\s/', $this->okUrl)) {
            throw InvalidConfiguration::attributeInvalidString('okUrl');
        }

        if (! preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $this->okUrl)) {
            throw InvalidConfiguration::attributeInvalidUrl('okUrl');
        }

        //failUrl
        if (! $this->failUrl) {
            throw InvalidConfiguration::attributeNotSpecified('failUrl');
        }

        if (preg_match('/\s/', $this->failUrl)) {
            throw InvalidConfiguration::attributeInvalidString('failUrl');
        }

        if (! preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $this->failUrl)) {
            throw InvalidConfiguration::attributeInvalidUrl('failUrl');
        }

        //shopUrl
        if (! $this->shopUrl) {
            throw InvalidConfiguration::attributeNotSpecified('shopUrl');
        }

        if (preg_match('/\s/', $this->shopUrl)) {
            throw InvalidConfiguration::attributeInvalidString('shopUrl');
        }

        if (! preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $this->shopUrl)) {
            throw InvalidConfiguration::attributeInvalidUrl('shopUrl');
        }

        //callbackUrl
        if (! $this->callbackUrl) {
            throw InvalidConfiguration::attributeNotSpecified('callbackUrl');
        }

        if (preg_match('/\s/', $this->callbackUrl)) {
            throw InvalidConfiguration::attributeInvalidString('callbackUrl');
        }

        if (! preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $this->callbackUrl)) {
            throw InvalidConfiguration::attributeInvalidUrl('callbackUrl');
        }

        //hashAlgorithm
        if (! $this->hashAlgorithm) {
            throw InvalidConfiguration::attributeNotSpecified('version du hachage (hashAlgorithm)');
        }

        if (preg_match('/\s/', $this->hashAlgorithm)) {
            throw InvalidConfiguration::attributeInvalidString('version du hachage (hashAlgorithm)');
        }

        //encoding
        if (! $this->encoding) {
            throw InvalidConfiguration::attributeNotSpecified('encodage des données (encoding)');
        }

        if (preg_match('/\s/', $this->encoding)) {
            throw InvalidConfiguration::attributeInvalidString('encodage des données (encoding)');
        }

        //sessionTimeout
        if (! $this->sessionTimeout) {
            throw InvalidConfiguration::attributeNotSpecified('délai d\'expiration de la session (sessionTimeout)');
        }

        if ((int) $this->sessionTimeout < 30 || (int) $this->sessionTimeout > 2700) {
            throw InvalidConfiguration::sessionimeoutValueInvalid();
        }
    }

    public function guardAgainstInvalidRequest($data)
    {
        //amount
        if ($data['amount'] === null) {
            throw InvalidRequest::amountNotSpecified();
        }

        if (! preg_match('/^\d+(\.\d{2})?$/', $data['amount'])) {
            throw InvalidRequest::amountValueInvalid();
        }

        //currency
        if ($data['currency'] === null) {
            throw InvalidRequest::currencyNotSpecified();
        }

        if (! is_string($data['currency']) || strlen($data['currency']) != 3) {
            throw InvalidRequest::currencyValueInvalid();
        }

        //orderid
        if ($data['orderid'] === null) {
            throw InvalidRequest::attributeNotSpecified('identifiant de la commande (orderid)');
        }

        if (! is_string($data['orderid']) || preg_match('/\s/', $data['orderid'])) {
            throw InvalidRequest::attributeInvalidString('identifiant de la commande (orderid)');
        }

        //email
        if ($data['email'] === null) {
            throw InvalidRequest::attributeNotSpecified('adresse électronique du client (email)');
        }

        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw InvalidRequest::emailValueInvalid();
        }

        //billToName
        if ($data['billToName'] === null) {
            throw InvalidRequest::attributeNotSpecified('nom du client (billToName)');
        }

        if (! is_string($data['billToName']) || $data['billToName'] === '') {
            throw InvalidRequest::attributeInvalidString('nom du client (billToName)');
        }

        //tel
        if (isset($data['tel']) && ! is_string($data['tel'])) {
            throw InvalidRequest::attributeInvalidString('téléphone du client (tel)');
        }

        //amountCur
        if (isset($data['amountCur']) && ! is_string($data['amountCur'])) {
            throw InvalidRequest::attributeInvalidString('montant de coversion (amountCur)');
        }

        //symbolCur
        if (isset($data['symbolCur']) && ! is_string($data['symbolCur'])) {
            throw InvalidRequest::attributeInvalidString('symbole de la devise de conversion (symbolCur)');
        }

        //description
        if (isset($data['description']) && ! is_string($data['description'])) {
            throw InvalidRequest::attributeInvalidString('description');
        }
    }
}
