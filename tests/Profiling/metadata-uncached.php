<?php

declare(strict_types=1);

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory;
use Zolex\VOM\Test\Fixtures\Profiling\ProfilingOrder;

$serializer = VersatileObjectMapperFactory::create();

/** @var ModelMetadataFactory $factory */
$factory = VersatileObjectMapperFactory::getMetadataFactory();

$data = [
    'meta' => ['id' => 42, 'created' => '2024-03-15 08:30:00'],
    'note' => 'Urgent shipment',
    'status' => 'ACTIVE',
    'active' => 'yes',
    'shipping_method' => 'EXPRESS',
    'street' => '123 Main St',
    'city' => 'Springfield',
    'country' => 'US',
    'address' => ['zip' => '12345'],
    'customer' => [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'tier' => 2,
    ],
    'items' => [
        [
            'sku' => 'PROD-001',
            'quantity' => 3,
            'unit_price' => 29.99,
            'category' => 'Electronics',
            'scheduled_at' => '2024-04-01 12:00:00',
            'tags' => ['sale', 'featured'],
        ],
        [
            'sku' => 'PROD-002',
            'quantity' => 1,
            'unit_price' => 9.99,
            'category' => 'Clothing',
            'scheduled_at' => null,
            'tags' => [],
        ],
    ],
    'METRIC_A' => 1500,
    'METRIC_B' => 320,
    'KPI' => ['VALUE' => 99],
];

// Wipe the in-memory cache before each iteration so metadata is always rebuilt from reflection
for ($i = 0; $i < 1000; ++$i) {
    $factory->resetLocalCache();
    $serializer->denormalize($data, ProfilingOrder::class);
}
