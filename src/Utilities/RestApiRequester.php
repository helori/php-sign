<?php

namespace Helori\PhpSign\Utilities;

use GuzzleHttp\Client;


class RestApiRequester
{
    /**
     * The API endpoint
     *
     * @var string
     */
    protected $endpoint;

    /**
     * The API endpoint
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Initialize a RestApiRequester
     *
     * @param  string  $apiKey
     * @param  string  $endpoint
     * @return array
     */
    public function __construct(string $apiKey = '', string $endpoint = '')
    {
        $this->setApiKey($apiKey);
        $this->setEndpoint($endpoint);
    }

    /**
     * Get the API key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set the API key
     *
     * @param  string  $apiKey
     * @return string
     */
    public function setApiKey($apiKey)
    {
        return $this->apiKey = $apiKey;
    }

    /**
     * Get the API endpoint
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * Set the API endpoint
     *
     * @param  string  $endpoint
     * @return string
     */
    public function setEndpoint($endpoint)
    {
        return $this->endpoint = rtrim($endpoint, '/');
    }

    /**
     * Send GET request
     *
     * @param  string $url
     * @param  array $query
     * @return mixed
     */
    public function get(string $url, array $query = [], $headers = [])
    {
        return $this->sendRequest('GET', $url, [], $query, [], $headers);
    }

    /**
     * Send POST request
     *
     * @param  string $url
     * @param  array $data
     * @return mixed
     */
    public function post(string $url, array $data = [], array $files = [], $headers = [])
    {
        return $this->sendRequest('POST', $url, $data, [], $files, $headers);
    }

    /**
     * Send PUT request
     *
     * @param  string $url
     * @param  array $data
     * @return mixed
     */
    public function put(string $url, array $data = [])
    {
        return $this->sendRequest('PUT', $url, $data, []);
    }

    /**
     * Send DELETE request
     *
     * @param  string $url
     * @return mixed
     */
    public function delete(string $url)
    {
        return $this->sendRequest('DELETE', $url);
    }

    /**
     * Send request
     *
     * @param  string $verb
     * @param  string $url
     * @param  array $data
     * @param  array $query
     * @param  array $files
     * @return mixed
     */
    public function sendRequest($verb, $url, $data = [], $query = [], $files = [], $headers = [])
    {
        $client = new Client();

        if(!empty($query)){
            $url .= '?'.http_build_query($query);
        }

        $config = [
            'headers' => array_merge([
                //'Content-Type' => 'application/json',
                //'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$this->apiKey,
            ], is_array($headers) ? $headers : []),
            //'auth' => ['anystring', $this->apiKey], 
            'http_errors' => false,
        ];

        if(!empty($files)){
            $config['multipart'] = [];
            foreach($files as $param => $abspath){
                $config['multipart'][] = [
                    'name' => $param,
                    'contents' => fopen($abspath, 'r'),
                ];
            }

            foreach($data as $key => $value){
                $config['multipart'][] = [
                    'name' => $key,
                    'contents' => $value,
                ];
            }

        }else if(!empty($data)){

            $config['json'] = $data;
        }

        $result = $client->request(
            $verb, 
            $this->endpoint.$url,
            $config
        );

        return $result;
    }
}
