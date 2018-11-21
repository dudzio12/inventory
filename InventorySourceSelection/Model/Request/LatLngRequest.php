<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySourceSelection\Model\Request;

use Magento\InventorySourceSelectionApi\Model\Request\LatLngRequestInterface;

/**
 * @inheritdoc
 */
class LatLngRequest implements LatLngRequestInterface
{
    /**
     * @var float
     */
    private $lat;

    /**
     * @var float
     */
    private $lng;

    /**
     * LatLng constructor.
     *
     * @param float $lat
     * @param float $lng
     */
    public function __construct(float $lat, float $lng)
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    /**
     * @inheritdoc
     */
    public function getLat(): float
    {
        return $this->lat;
    }

    /**
     * @inheritdoc
     */
    public function getLng(): float
    {
        return $this->lng;
    }

    /**
     * @inheritdoc
     */
    public function getAsString(): string
    {
        return $this->getLat() . ',' . $this->getLng();
    }
}
