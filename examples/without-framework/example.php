<?php

use App\Model\Person;
use Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory;
use Zolex\VOM\VersatileObjectMapper;

require __DIR__.'/vendor/autoload.php';

// create the VOM instance
$objectMapper = VersatileObjectMapperFactory::create();

// source data
$data = [
    'firstname' => 'Jane',
    'surname' => 'Doe',
    'street' => 'Samplestreet 123',
    'city' => 'Worsthausen',
    'zip' => '12345',
    'country_name' => 'United Kingdom',
    'email_address' => 'jane.doe@coxautoinc.com',
    'phone' => '0123456789',
];

// create the model instance
$person = $objectMapper->denormalize($data, Person::class);

echo "INPUT DATA:\n";
print_r($data);

echo "\n\nMAPPED MODEL:\n";
print_r($person);
