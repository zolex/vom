<?php

namespace App\Controller;

use App\Model\Person;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Zolex\VOM\VersatileObjectMapper;

class VomController
{
    public function __construct(private VersatileObjectMapper $objectMapper)
    {
    }

    #[Route('/vom-action')]
    public function vomAction(): JsonResponse
    {
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

        $entity = $this->objectMapper->denormalize($data, Person::class);

        return new JsonResponse($entity);
    }
}
