<?php

namespace wlec\Framework\Rest;

require_once __DIR__ . '/RestClient.php';

class ApiClient extends RestClient {

    /**
     * Disable RestClient Cache
     * @var int
     */
    protected $cacheTTL     = 0;
    /**
     * @var string
     */
    protected $cacheDir     = '/tmp/';
    /**
     * @var int
     */
    protected $ttlTolerance = 3600;
    /**
     * @var string
     */
    private $uriBase        = '';
    /**
     * @var string
     */
    private $uri            = '';

    /**
     * Api constructor.
     * @param string $url
     */
    public function __construct($url) {
        $protocol = 'http';
        $port     = 80;
        if (strpos($url, '://') !== false) {
            list ($protocol, $url) = explode('://', $url);
            if ($protocol === 'https') {
                $port = 443;
            }
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
        $this->addRequestHeader('Referer', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
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

        if ($requestMethod !== 'GET') {
            // POST, PUT, DELETE, etc: No Cache
            return parent::handleRequest($uri, $isPostRequest, $postData, $requestMethod);
        }

        $cacheFilename  = $this->cacheDir . 'api-' . md5($uri);
        $cacheFileFound = false;
        if (is_file($cacheFilename)) {
            $cacheFileFound = true;
            $cacheContent   = file_get_contents($cacheFilename);
            $cacheEOL       = substr($cacheContent, 0, 10);
            $cacheTTL       = $cacheEOL - time();
            $json           = substr($cacheContent, 10);
            $data           = json_decode($json, !$this->jsonDecodeObject);

            // Cache is still valid
            if ($cacheTTL > 0) {
                return $data;
            }

            // Cache is outdated, but not too old: Refresh it (for concurrent requests)
            if ($cacheTTL > -$this->ttlTolerance) {
                touch($cacheFilename);
            }
        }

        // Fetch Remote API Data
        $data = parent::handleRequest($uri, $isPostRequest, $postData, $requestMethod);

        // Extract Header and determine Cache TTL
        if (preg_match('/Cache-Control: max-age=(\d+)/', $this->header, $var)) {
            $cacheTTL = (int) $var[1];
            $cacheEOL = time() + $cacheTTL;
            file_put_contents($cacheFilename, $cacheEOL . $this->response);
        } else if ($cacheFileFound) {
            // No Cache, but File found: Delete it
            unset($cacheFilename);
        }

        return $data;
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
