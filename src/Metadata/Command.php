<?php

/**
 * @see       https://github.com/event-engine/php-inspectio-graph-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-inspectio-graph-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-inspectio-graph-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\InspectioGraphCody\Metadata;

use EventEngine\InspectioGraph\Metadata\CommandMetadata;

final class Command implements CommandMetadata
{
    /**
     * @var bool
     */
    private $newAggregate = false;

    /**
     * @var string|null
     */
    private $schema;

    private function __construct()
    {
    }

    public static function fromJsonMetadata(string $json): self
    {
        $self = new self();

        if (empty($json)) {
            return $self;
        }
        $data = NodeJsonMetadataFactory::decodeJson($json);

        $self->newAggregate = $data['newAggregate'] ?? false;

        if (! empty($data['schema'])) {
            $self->schema = NodeJsonMetadataFactory::encodeJson($data['schema']);
        }

        return $self;
    }

    public function newAggregate(): bool
    {
        return $this->newAggregate;
    }

    public function schema(): ?string
    {
        return $this->schema;
    }
}
