<?php

//header('Content-type: text/html; charset=utf-8');

require_once 'init.php';

if (isset($_REQUEST['address'])) {

    $bbox = [
        //limit bbox for Moscow - first transport ring
        'Moscow1stRing' => '37.582485,55.730069~37.657157,55.774216',
        //second transport ring
        'Moscow2ndRing' => '37.554505,55.697508~37.706187,55.795890',
        //limit bbox for Moscow - third transport ring
        'Moscow3rdRing' => '36.506466,55.022737~38.979999,56.446608',
    ];

    $address = new Address();
    //set search area limit
    $address->setBboxFilter($bbox['Moscow2ndRing']);

    $data = $address->getAddressInfo($_REQUEST['address']);
//    echo '<pre>' . print_r($data, true) . '</pre>';
    echo json_encode($data);
}