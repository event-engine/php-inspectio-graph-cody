<?php

/**
 * @see       https://github.com/event-engine/php-inspectio-graph-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-inspectio-graph-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-inspectio-graph-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\InspectioGraphCody\Metadata;

use EventEngine\InspectioGraph\Metadata\PolicyMetadata;

final class Policy implements PolicyMetadata
{
    /**
     * @var array
     */
    private $streams = [];

    private function __construct()
    {
    }

    public static function fromJsonMetadata(string $json): self
    {
        $self = new self();

        if (! empty($json)) {
            $data = \json_decode($json, true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR);

            $self->streams = $data['streams'] ?? null;
        }

        return $self;
    }

    public function streams(): array
    {
        return $this->streams;
    }
}
