<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Person;
use Zolex\VOM\Serializer\VersatileObjectMapper;

class PersonStateProvider implements ProviderInterface
{
    public function __construct(private VersatileObjectMapper $serializer)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
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

        $person = $this->serializer->denormalize($data, Person::class);
        $person->id = $uriVariables['id'];

        return $person;
    }
}
