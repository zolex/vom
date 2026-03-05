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

#[VOM\Model]
class ProfilingOrderItem
{
    // Properties set by VOM after factory instantiation
    #[VOM\Property('[unit_price]')]
    public float $unitPrice;

    // Pure enum — VOM maps the string to the enum case name
    #[VOM\Property]
    public ProfilingCategory $category;

    #[VOM\Property('[scheduled_at]')]
    public ?\DateTimeImmutable $scheduledAt;

    /** @var string[] */
    #[VOM\Property]
    public array $tags;

    private string $sku;
    private int $quantity;

    private function __construct()
    {
    }

    // Factory creates the instance from required identity fields; remaining
    // properties (unitPrice, category, scheduledAt, tags) are set by VOM afterwards
    #[VOM\Factory]
    public static function create(
        #[VOM\Argument]
        string $sku,
        #[VOM\Argument]
        int $quantity,
    ): self {
        $item = new self();
        $item->sku = $sku;
        $item->quantity = $quantity;

        return $item;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
