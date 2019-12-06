<?php

namespace Wlec\Framework\Cache;

class Datastore {

    /**
     * @var string
     */
    protected $folder  = '/tmp/';

    /**
     * @var string
     */
    protected $context = '';

    /**
     * Datastore constructor.
     * @param string $context
     */
    public function __construct( $context = '' ) {
        if ($context) {
            $this->context = $context . '_';
        }
    }

    /**
     * @param $folder
     */
    public function setFolder($folder) {
        $this->folder = $folder;
    }

    /**
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    public function read ( $key ) {
        $storageFile = $this->getStorageFilename($key);
        if (!is_file($storageFile)) {
            throw new \Exception("Datastore File not found: $key", 6001);
        }
        $json = file_get_contents($storageFile);
        return json_decode($json, true);
    }

    /**
     * @param $data
     * @param string $key
     * @return string
     */
    public function write ( $data, $key = '' ) {
        if (empty($key)) {
            $key = $this->generateKey();
        }
        $storageFile = $this->getStorageFilename($key);
        file_put_contents($storageFile, json_encode($data));
        return $key;
    }

    /**
     * @param string $key
     * @return bool
     * @throws \Exception
     */
    public function delete ( $key ) {
        $storageFile = $this->getStorageFilename($key);
        if (!is_file($storageFile)) {
            throw new \Exception("Datastore File not found: $key", 6001);
        }
        unlink($storageFile);
        if (is_file($storageFile)) {
            throw new \Exception("Not able to delete Datastore File: $key", 6002);
        }
        return true;
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getStorageFilename ( $key ) {
        return $this->folder . $this->context . 'datastore_' . rawurlencode($key) . '.json';
    }

    /**
     * @return string
     */
    protected function generateKey() {
        $key = uniqid();
        return $key;
    }

}
