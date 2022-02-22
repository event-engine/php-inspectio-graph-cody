<?php

/**
 * @see       https://github.com/event-engine/php-inspectio-graph-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-inspectio-graph-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-inspectio-graph-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\InspectioGraphCody;

use EventEngine\InspectioGraph\Metadata;
use EventEngine\InspectioGraph\RoleType;

final class Role extends Vertex implements RoleType
{
    protected const TYPE = self::TYPE_ROLE;

    /**
     * @var Metadata\Metadata|null
     */
    protected $metadataInstance;

    public function metadataInstance(): ?Metadata\Metadata
    {
        return $this->metadataInstance;
    }
}
