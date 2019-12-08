<?php

namespace wlec\Framework\Rest;

/**
 * Class RestClient
 */
class RestClient {
    /**
     * @var string
     */
    protected $domain        = '';
    /**
     * @var int
     */
    protected $port          = 433;
    /**
     * @var string
     */
    protected $protocol      = 'https';
    /**
     * @var string
     */
    protected $cacheDir      = '/tmp/';
    /**
     * @var int
     */
    protected $cacheTTL      = 60;
    /**
     * @var string
     */
    protected $requestMethod = 'GET';
    /**
     * @var string
     */
    protected $url;
    /**
     * @var array
     */
    protected $requestHeader = [];
    /**
     * @var string
     */
    protected $request       = '';
    /**
     * @var string
     */
    protected $header;
    /**
     * @var string
     */
    protected $response;
    /**
     * @var string
     */
    protected $accessToken;
    /**
     * @var
     */
    protected $basicAuthentication;
    /**
     * @var array
     */
    protected $data;
    /**
     * @var bool
     */
    protected $jsonDecodeObject = false;

    /**
     * RestClient constructor.
     *
     * @param string $domain
     * @param int    $port
     */
    public function __construct ($domain = '', $port = 443) {
        $this->domain = $domain;
        $this->port   = $port;
    }

    /**
     * @param string $domain
     */
    public function setDomain ($domain) {
        $this->domain = $domain;
    }

    /**
     * @param int $port
     */
    public function setPort ($port) {
        $this->port = $port;
    }

    /**
     * @param string $protocol
     */
    public function setProtocol ($protocol) {
        $this->protocol = $protocol;
    }

    /**
     * @param mixed $token
     */
    public function setToken ($token) {
        $this->token = $token;
    }

    /**
     * @return mixed
     */
    public function getResponse () {
        return $this->response;
    }

    /**
     * @return mixed
     */
    public function getData () {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getRequestMethod () {
        return $this->requestMethod;
    }

    /**
     * @return mixed
     */
    public function getUrl () {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getRequest () {
        return $this->request;
    }

    /**
     * @param array $requestHeader
     */
    public function addRequestHeader ($requestHeader) {
        $this->requestHeader[] = $requestHeader;
    }

    /**
     * @param mixed $accessToken
     */
    public function setAccessToken ($accessToken) {
        $this->accessToken = $accessToken;
    }

    /**
     * @param mixed $basicAuthentication
     */
    public function setBasicAuthentication($userName, $password) {
        $this->basicAuthentication = base64_encode("$userName:$password");
    }

    /**
     * @param bool $jsonDecodeObject
     */
    public function setJsonDecodeObject($jsonDecodeObject = true) {
        $this->jsonDecodeObject = $jsonDecodeObject;
    }

    /**
     * @param $uri
     *
     * @return mixed
     */
    public function get($uri, $useCache = false) {
        if ($useCache && $this->cacheTTL > 0) {
            $cacheFilename = $this->cacheDir . 'rest-' . md5($this->domain . $uri);
            if (is_file($cacheFilename)) {
                if (time() - filemtime($cacheFilename) < $this->cacheTTL) {
                    return json_decode(file_get_contents($cacheFilename), !$this->jsonDecodeObject);
                }
            }
        }
        $result = $this->handleRequest($uri, false);
        if ($useCache && $this->cacheTTL > 0) {
            file_put_contents($cacheFilename, json_encode($result));
        }

        return $result;
    }

    /**
     * @param $uri
     * @param $data
     *
     * @return mixed
     */
    public function post ( $uri, $data ) {
        return $this->handleRequest($uri, true, $data, 'POST');
    }

    /**
     * @param $uri
     * @param $data
     *
     * @return mixed
     */
    public function patch ( $uri, $data ) {
        return $this->handleRequest($uri, true, $data, 'PATCH');
    }

    /**
     * @param $uri
     * @param $data
     *
     * @return mixed
     */
    public function put ( $uri, $data ) {
        return $this->handleRequest($uri, true, $data, 'PUT');
    }

    /**
     * @param $uri
     * @return mixed|string
     */
    public function delete ( $uri ) {
        return $this->handleRequest($uri, false, null, 'DELETE');
    }


    /**
     * @param $uri
     * @param bool $isPostRequest
     * @param null $postData
     * @param string $requestMethod
     * @return array|mixed|string|null
     */
    public function handleRequest ( $uri, $isPostRequest = false, $postData = null, $requestMethod = 'GET' ) {
        $this->response      = null;
        $this->data          = null;
        $this->requestHeader = [];

        try {
            $body = '';
            $this->url = $this->protocol . '://' . $this->domain;
            if ($this->port) {
				$this->url .= ':' . $this->port;
			}
            $this->url .= $uri;

            $ch = curl_init($this->url);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            if ($isPostRequest) {
                $body = is_string($postData) ? $postData : json_encode($postData);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestMethod);
                $this->addRequestHeader('Content-Type: application/json');
                $this->addRequestHeader('Content-Length: ' . strlen($body));
                $this->requestMethod = $requestMethod;
            } else if ($requestMethod != 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestMethod);
                $this->requestMethod = $requestMethod;
            }
            if (isset($this->accessToken)) {
                $this->addRequestHeader('Authorization: Oauth ' . $this->accessToken);
            } else  if (isset($this->basicAuthentication)) {
                $this->addRequestHeader('Authorization: Basic ' . $this->basicAuthentication);
            }
            if (!empty($this->requestHeader)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->requestHeader);
            }

            // Execute Request, Extract Header + Content
            $response       = curl_exec($ch);
            $headerSize     = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $this->header   = substr($response, 0, $headerSize);
            $this->response = substr($response, $headerSize);
            curl_close($ch);

            //  Store Request
            $this->request = trim(join("\n", $this->requestHeader) . "\n\n" . $body);
        } catch (Exception $e) {
            $this->error('CURL Request Error: ' . $e->getMessage());
        }
        //  Handle Result
        try {
            $this->data = json_decode($this->response, !$this->jsonDecodeObject);
        } catch (Exception $e) {
            $this->error('JSON Decode Error: ' . $e->getMessage());
        }
        if ($this->data === null) {
            return "Invalid JSON (or NULL): " . $this->response;
        }
        return $this->data;
    }

    /**
     * @param $message
     */
    private function error ( $message ) {
        print "ERROR: " . $message;
        exit;
    }

}
