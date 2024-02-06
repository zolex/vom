<?php

namespace App\Controller;

use App\Model\Person;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Zolex\VOM\Serializer\VersatileObjectMapper;

class VomController
{
    public function __construct(private VersatileObjectMapper $serializer)
    {
    }

    #[Route('/deserialize')]
    public function deserializeAction(): JsonResponse
    {
        $json = '{"firstname":"Jane","surname":"Doe","street":"Samplestreet 123","city":"Worsthausen","zip":"12345","country_name":"United Kingdom","email_address":"jane.doe@coxautoinc.com","phone":"0123456789"}';
        $person = $this->serializer->deserialize($json, Person::class, 'json');

        return new JsonResponse($person);
    }

    #[Route('/denormalize')]
    public function denormalizeAction(): JsonResponse
    {
        $data =
            [
                'firstname' => 'Jane',
                'surname' => 'Doe',
                'street' => 'Samplestreet 123',
                'city' => 'Worsthausen',
                'zip' => '12345',
                'country_name' => 'United Kingdom',
                'email_address' => 'jane.doe@coxautoinc.com',
                'phone' => '0123456789',
            ];

        $person = $this->serializer->denormalize($data, Person::class, 'json');

        return new JsonResponse($person);
    }
}
