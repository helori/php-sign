<?php

namespace Helori\PhpSign\Utilities;

use PhpXmlRpc\PhpXmlRpc;
use PhpXmlRpc\Client;
use PhpXmlRpc\Request;
use PhpXmlRpc\Value;
use Exception;


class XmlRpcRequester
{
    /**
     * The API endpoint
     *
     * @var string
     */
    protected $endpoint;

    /**
     * The username
     *
     * @var string
     */
    protected $username;

    /**
     * The password
     *
     * @var string
     */
    protected $password;

    /**
     * Initialize a XmlRpcRequester
     *
     * @param  string  $username
     * @param  string  $password
     * @param  string  $endpoint
     * @return array
     */
    public function __construct(string $username = '', string $password = '', string $endpoint = '')
    {
        $GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';
        PhpXmlRpc::importGlobals();

        $this->username = $username;
        $this->password = $password;
        $this->endpoint = $endpoint;
    }

    /**
     * Get the username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Get the password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Get the endpoint
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * Set the username
     *
     * @param  string  $username
     * @return string
     */
    public function setUsername($username)
    {
        return $this->username = $username;
    }

    /**
     * Set the password
     *
     * @param  string  $password
     * @return string
     */
    public function setPassword($password)
    {
        return $this->password = $password;
    }

    /**
     * Set the API endpoint
     *
     * @param  string  $endpoint
     * @return string
     */
    public function setEndpoint($endpoint)
    {
        return $this->endpoint = $endpoint;
    }

    /**
     * Send request
     *
     * @param  string $requestName
     * @param  array $params
     * @return mixed
     */
    public function sendRequest($requestName, $params)
    {
        $client = new Client($this->endpoint);
        $client->username = $this->username;
        $client->password = $this->password;

        // SSL verification (should be enabled in production)
        $client->setSSLVerifyHost(0);
        $client->setSSLVerifyPeer(0);
        $client->setDebug(0);

        $request = new Request($requestName, $params);
        $result = $client->send($request);
        
        if($result->faultCode()){
            throw new \Exception($result->faultString(), $result->faultCode());
        }
        return $result->value();
    }
}
