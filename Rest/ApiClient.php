<?php

namespace Wlec\Framework\Rest;

require_once __DIR__ . '/RestClient.php';

class ApiClient extends RestClient {

    /**
     * @var string
     */
    private $uriBase = '';

    /**
     * @var string
     */
    private $uri     = '';

    /**
     * Api constructor.
     * @param string $url
     */
    public function __construct($url) {
        $protocol = 'https';
        $port     = 80;
        if (strpos($url, '://') !== false) {
            list ($protocol, $url) = explode('://', $url);
        }
        if (strpos($url, '/') !== false) {
            list ($url, $uriBase) = explode('/', $url, 2);
            $this->uriBase = '/' . $uriBase;
        }
        if (strpos($url, ':') !== false) {
            list ($url, $port) = explode(':', $url, 2);
        }
        parent::__construct($url, $port);
        $this->setProtocol($protocol);
    }

    /**
     * @param string $uri
     * @param bool $isPostRequest
     * @param array $postData
     * @param string $requestMethod
     * @return mixed
     */
    public function handleRequest ( $uri, $isPostRequest = false, $postData = null, $requestMethod = 'GET' ) {
        $uri = preg_replace('/\/+/', '/', $this->uriBase . '/' . $uri);
        return parent::handleRequest($uri, $isPostRequest, $postData, $requestMethod);
    }

    /**
     * @param string $uri
     * @param bool $useCache
     * @return mixed
     */
    public function get ( $uri, $useCache = false ) {
        $this->uri = '';
        return parent::get($uri, false);
    }

    /**
     * @param array $filter
     * @return mixed
     */
    public function find ( $filter = [] ) {
        if (!empty($filter)) {
            $this->uri .= '?' . http_build_query($filter);
        }
        return $this->get($this->uri);
    }

    /**
     * @param string $name
     * @param array $args
     * @return $this
     */
    public function __call ($name, $args) {
        if (substr($name, 0, 3) == 'get') {
            $this->uri .= '/' . strtolower(substr($name, 3));
            if (!empty($args) && is_numeric($args[0])) {
                $this->uri .= '/' . $args[0];
            }
            return $this->get($this->uri);
        } else {
            $this->uri .= '/' . strtolower($name);
            if (!empty($args) && is_numeric($args[0])) {
                $this->uri .= '/' . $args[0];
            }
            return $this;
        }
    }
}
