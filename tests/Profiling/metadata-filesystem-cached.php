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

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Zolex\VOM\Metadata\Factory\CachedModelMetadataFactory;
use Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory;
use Zolex\VOM\Test\Fixtures\Profiling\ProfilingOrder;

$cacheDir = sys_get_temp_dir().'/vom-profiling-cache';

$cache = new FilesystemAdapter(namespace: 'vom_profiling', defaultLifetime: 0, directory: $cacheDir);

// Start with a clean filesystem cache so the warm-up below actually writes to disk
$cache->clear();

$serializer = VersatileObjectMapperFactory::create($cache);

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

// Warm up: build metadata from reflection and persist it to the filesystem cache
$serializer->denormalize($data, ProfilingOrder::class);

/** @var CachedModelMetadataFactory $factory */
$factory = VersatileObjectMapperFactory::getMetadataFactory();

// Wipe only the in-memory local cache before each iteration so metadata is always
// deserialized from the filesystem cache (disk read + unserialize), never from reflection
for ($i = 0; $i < 1000; ++$i) {
    $factory->resetLocalCache();
    $serializer->denormalize($data, ProfilingOrder::class);
}
