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

namespace Zolex\VOM\Test\Fixtures\Profiling;

use Zolex\VOM\Mapping as VOM;

/**
 * Kitchen-sink fixture exercising a broad set of VOM features in one mapping hierarchy:
 *
 *  - Deep accessors:         [meta][id], [meta][created]
 *  - Backed string enum:     status → ProfilingStatus
 *  - Bool with true/false:   active (trueValue: 'yes', falseValue: 'no')
 *  - Nullable property:      note
 *  - Flattened nesting:      address via accessor: false
 *  - Nested model:           customer (ProfilingCustomer uses root: true inside)
 *  - Array collection:       items (ProfilingOrderItem[] uses a Factory method)
 *  - Accessor-list:          metrics (ProfilingMetric[] keyed by named accessors)
 *  - Normalizer method:      getOrderSummary()
 */
#[VOM\Model]
class ProfilingOrder
{
    // Deep accessor path
    #[VOM\Property('[meta][id]')]
    public int $id;

    // Backed string enum — VOM maps the raw string to the enum case value
    #[VOM\Property]
    public ProfilingStatus $status;

    // Boolean with explicit true/false raw values
    #[VOM\Property(trueValue: 'yes', falseValue: 'no')]
    public bool $active;

    // Deep accessor + DateTime auto-conversion
    #[VOM\Property('[meta][created]')]
    public \DateTime $createdAt;

    // Nullable string — may be absent from the input
    #[VOM\Property]
    public ?string $note;

    // accessor: false — ProfilingAddress fields are resolved at the same data level as ProfilingOrder
    #[VOM\Property(accessor: false)]
    public ProfilingAddress $address;

    // Regular nested model under [customer]
    #[VOM\Property]
    public ProfilingCustomer $customer;

    /**
     * Array collection of nested models.
     * ProfilingOrderItem uses a #[VOM\Factory] for construction.
     *
     * @var ProfilingOrderItem[]
     */
    #[VOM\Property]
    public array $items;

    /**
     * Accessor-list collection: each entry in the list is built from a distinct
     * accessor path and wrapped in a ProfilingMetric value object.
     *
     * @var ProfilingMetric[]
     */
    #[VOM\Property(accessor: [
        'metric_a' => '[METRIC_A]',
        'metric_b' => '[METRIC_B]',
        'kpi' => '[KPI][VALUE]',
    ])]
    public array $metrics;

    // Value-map with explicit accessor: maps raw string codes to human-readable labels
    #[VOM\Property('[shipping_method]', map: [
        'STANDARD' => 'Standard Shipping',
        'EXPRESS' => 'Express Shipping',
        'OVERNIGHT' => 'Overnight Shipping',
    ])]
    public string $shippingMethod = 'Standard Shipping';

    // Normalizer virtual property — merged into the normalized output array
    #[VOM\Normalizer]
    public function getOrderSummary(): array
    {
        return [
            'item_count' => \count($this->items ?? []),
            'metric_count' => \count($this->metrics ?? []),
        ];
    }
}
