<?php

use App\Model\Person;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\PropertyMetadataFactory;
use Zolex\VOM\VersatileObjectMapper;

require __DIR__ .'/vendor/autoload.php';

// create the VOM instance
$propertyAccessor = PropertyAccess::createPropertyAccessor();
$propMetadataFactory = new PropertyMetadataFactory();
$modelMetadataFactory = new ModelMetadataFactory($propMetadataFactory);
$objectMapper = new VersatileObjectMapper($modelMetadataFactory, $propertyAccessor);

// source data
$data = [
    'firstname' => 'Jane',
    'surname' => 'Doe',
    'street' => 'Samplestreet 123',
    'city' => 'Worsthausen',
    'zip' => '12345',
    'country_name' => 'United Kingdom',
    'email_address' => 'jane.doe@coxautoinc.com',
    'phone' => '0123456789'
];

// create the model instance
$person = $objectMapper->denormalize($data, Person::class);


echo "INPUT DATA:\n";
print_r($data);

echo "\n\nMAPPED MODEL:\n";
print_r($person);
