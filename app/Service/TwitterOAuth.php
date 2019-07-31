<?php

namespace App\Service;

/**
 * TwitterOAuth class for interacting with the Twitter API.
 *
 */
class TwitterOAuth
{
    /** @var string consumerKey */
    private $consumerKey;
    /** @var string consumerSecret */
    private $consumerSecret;
    /** @var string oauthToken */
    private $oauthToken;
    /** @var string oauthTokenSecret */
    private $oauthTokenSecret;
    /** @var array parameters */
    private $parameters = [];

    /**
     * Constructor
     *
     * @param string $consumerKey      The Application Consumer Key
     * @param string $consumerSecret   The Application Consumer Secret
     * @param string $oauthToken       The Client Token (optional)
     * @param string $oauthTokenSecret The Client Token Secret (optional)
     */
    public function __construct($consumerKey, $consumerSecret, $oauthToken, $oauthTokenSecret)
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->oauthToken = $oauthToken;
        $this->oauthTokenSecret = $oauthTokenSecret;

        $this->parameters = [
            "oauth_version" => "1.0",
            "oauth_nonce" => md5(microtime() . mt_rand()),
            "oauth_timestamp" => time(),
            "oauth_consumer_key" => env('CONSUMER_KEY'),
            "oauth_token" => env('TOKEN'),
            "oauth_signature_method" => "HMAC-SHA1",
        ];
    }

    /**
     * @param array $params
     */
    public function search($params)
    {
        $parameters = array_merge($this->parameters, $params);

        $base = [
            'GET',
            'https://api.twitter.com/1.1/search/tweets.json',
            $this->buildHttpQuery($parameters)
        ];

        $authorization = $this->getAuthorization($base, $parameters);

        return $this->request($authorization, $params);
    }

    /**
     * @param array $params
     */
    public function buildHttpQuery($params) {
        if (empty($params)) {
            return '';
        }
        $keys = array_map('rawurlencode', (array_keys($params)));
        $values = array_map('rawurlencode', (array_values($params)));
        $params = array_combine($keys, $values);

        // Parameters are sorted by name, using lexicographical byte value ordering.
        // Ref: Spec: 9.1.1 (1)
        uksort($params, 'strcmp');

        $pairs = [];
        foreach ($params as $parameter => $value) {
            if (is_array($value)) {
                // If two or more parameters share the same name, they are sorted by their value
                // Ref: Spec: 9.1.1 (1)
                // June 12th, 2010 - changed to sort because of issue 164 by hidetaka
                sort($value, SORT_STRING);
                foreach ($value as $duplicateValue) {
                    $pairs[] = $parameter . '=' . $duplicateValue;
                }
            } else {
                $pairs[] = $parameter . '=' . $value;
            }
        }

        return implode('&', $pairs);
    }

    /**
     * @param array $base
     * @param array $parameters
     */
    public function getAuthorization($base, $parameters)
    {
        $signatureBase = (implode('&', array_map('rawurlencode',$base)));

        $parts = [env('CONSUMER_SECRET'), env('TOKEN_SECRET')];

        $parts = array_map('rawurlencode', $parts);
        $key = implode('&', $parts);

        $parameters['oauth_signature'] = base64_encode(hash_hmac('sha1', $signatureBase, $key, true));

        $first = true;
        $authorization = 'Authorization: OAuth';
        foreach ($parameters as $k => $v) {
            if (substr($k, 0, 5) != "oauth") {
                continue;
            }
            $authorization .= ($first) ? ' ' : ', ';
            $authorization .= rawurlencode($k) . '="' . rawurlencode($v) . '"';
            $first = false;
        }

        return $authorization;
    }

    /**
     * @param string $authorization
     * @param array $params
     */
    public function request($authorization, $params)
    {
        $options = [
            // CURLOPT_VERBOSE => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_USERAGENT => 'TwitterOAuth (+https://twitteroauth.com)',
        ];

        $options[CURLOPT_ENCODING] = 'gzip';
        $options[CURLOPT_URL] = 'https://api.twitter.com/1.1/search/tweets.json';
        $options[CURLOPT_HTTPHEADER] = ['Accept: application/json', $authorization, 'Expect:'];
        $options[CURLOPT_URL] .= '?' . $this->buildHttpQuery($params);

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, $options);
        $response = curl_exec($curlHandle);

        // echo curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

        $parts = explode("\r\n\r\n", $response);
        return json_decode(array_pop($parts));
    }
}
