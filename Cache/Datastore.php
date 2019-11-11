<?php

namespace Wlec\Framework\Cache;

class Datastore {

    /**
     * @var string
     */
    private $folder = '/tmp/';

    /**
     * Datastore constructor.
     * @param string $folder
     */
    public function __construct( $folder = '' ) {
        if ($folder) {
            $this->folder = $folder;
        }
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
            $key = uniqid();
        }
        $storageFile = $this->getStorageFilename($key);
        file_put_contents($storageFile, json_encode($data));
        return $key;
    }

    /**
     * @param string $key
     * @return string
     */
    private function getStorageFilename ( $key ) {
        return $this->folder . 'datastore_' . rawurlencode($key) . '.json';
    }

}
