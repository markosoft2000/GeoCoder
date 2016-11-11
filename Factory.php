<?php

/**
 * Class Factory
 *
 * Provides classes
 */
class Factory {
    const YANDEX_GEO_API = 'yandex_geo_api';

    /**
     * Returns object of a given class name
     * @param $class - class name
     * @param $params - init params
     * @return \Yandex\Geo\Api
     * @throws Exception
     */
    public static function create($class, $params) {
        switch ($class) {
            case self::YANDEX_GEO_API:
                $version = isset($params['version']) ? $params['version']: null;
                return new \Yandex\Geo\Api($version);
            default:
                throw new Exception('Class not found');
        }
    }
}