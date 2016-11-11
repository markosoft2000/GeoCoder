<?php

/**
 * Class Address
 */
class Address {

    /**
     * @var integer
     */
    const ADDRESS_LIMIT = 10;

    /**
     * @var integer
     */
    const DISTRICT_LIMIT = 1;

    /**
     * @var integer
     */
    const METRO_LIMIT = 5;

    /**
     * @var \Yandex\Geo\Api
     */
    protected $_api;

    /**
     * @var string
     */
    protected $_apiVersion = '1.x';

    /**
     * @var string
     */
    public $addressQuery;

    protected $_filter;

    /**
     * @var array
     */
    protected $_results = [];

    /**
     * Address constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->_api = Factory::create(Factory::YANDEX_GEO_API, ['version' => $this->_apiVersion]);
    }

    public function setBboxFilter($bbox)
    {
        $this->_filter['bbox'] = (string) $bbox;
    }

    /**
     * executes search
     */
    protected function find() {
        $this->performAddressInfo();
        $this->performDistrictInfo();
        $this->performMetroClosestInfo();
    }

    /**
     * General information collection method
     * @param string $address
     * @return array|null
     */
    public function getAddressInfo($address = '')
    {
        if (empty($address)) {
//            throw new Exception('Address is empty');
            return null;
        }

        $this->addressQuery = (string) $address;
        $this->find();

        return $this->_results;
    }

    /**
     * gets address information (country, city, street, house and coordinates)
     * @throws \Yandex\Geo\Exception
     * @throws \Yandex\Geo\Exception\CurlError
     * @throws \Yandex\Geo\Exception\ServerError
     */
    protected function performAddressInfo()
    {
        $this->_api->setQuery($this->addressQuery);
        $this->_api->resetAreaBoxLimit();

        if (!empty($this->_filter['bbox'])) {
            $this->_api->setAreaBoxLimit($this->_filter['bbox']);
        }

        $this->_api
            ->setLimit(self::ADDRESS_LIMIT)
            ->setLang(\Yandex\Geo\Api::LANG_RU)
            ->load();
        $response = $this->_api->getResponse();
        $results = $response->getList();

        $this->_results = [];

        /** @var Yandex\Geo\GeoObject $result */
        foreach ($results as $result) {
            $this->_results[] = [
                'address' => $result->getAddress(),
                'country' => $result->getCountry(),
                'city' => $result->getAdministrativeAreaName(),
                'street' => $result->getThoroughfareName(),
                'house' => $result->getPremiseNumber(),
                'longitude' => $result->getLongitude(),
                'latitude' => $result->getLatitude(),
                'district' => $result->getLocalityName(),
            ];
        }
    }

    /**
     * gets district information
     * @throws \Yandex\Geo\Exception
     * @throws \Yandex\Geo\Exception\CurlError
     * @throws \Yandex\Geo\Exception\ServerError
     */
    protected function performDistrictInfo()
    {
        $this->_api->resetAreaBoxLimit();
        foreach ($this->_results as &$result) {
            $this->_api->setPoint($result['longitude'], $result['latitude']);
            $this->_api
                ->setLimit(self::DISTRICT_LIMIT)
                ->setLang(\Yandex\Geo\Api::LANG_RU)
                ->setKind(\Yandex\Geo\Api::KIND_DISTRICT)
                ->load();
            $response = $this->_api->getResponse();
            /** @var Yandex\Geo\GeoObject $result */
            $districtData = $response->getFirst();

            if ($districtData) {
                $result['sub_district'] = $districtData->getDependentLocalityName();

                if ($result['district'] == $result['city']) {
                    $result['district'] = $result['sub_district'];
                }
            }
        }
    }

    /**
     * provides information about closest metro station
     * @throws \Yandex\Geo\Exception
     * @throws \Yandex\Geo\Exception\CurlError
     * @throws \Yandex\Geo\Exception\ServerError
     */
    protected function performMetroClosestInfo()
    {
        $this->_api->resetAreaBoxLimit();
        foreach ($this->_results as &$result) {
            $this->_api->setPoint($result['longitude'], $result['latitude']);
            $this->_api
                ->setLimit(self::METRO_LIMIT)
                ->setLang(\Yandex\Geo\Api::LANG_RU)
                ->setKind(\Yandex\Geo\Api::KIND_METRO)
                ->load();
            $response = $this->_api->getResponse();
            /** @var Yandex\Geo\GeoObject $result */
            $metroData = $response->getList();

            if (!$metroData) {
                return;
            }

            /** @var \Yandex\Geo\GeoObject $metro */
            foreach ($metroData as $metro) {
                $result['metroClosestList'][] = [
                    'METRO_STATION_LINE' => $metro->getThoroughfareName(),
                    'METRO_STATION_NAME' => $metro->getPremiseName(),
                    'METRO_STATION_LONGITUDE' => $metro->getLongitude(),
                    'METRO_STATION_LATITUDE' => $metro->getLatitude(),
                ];
            }
        }
    }
}