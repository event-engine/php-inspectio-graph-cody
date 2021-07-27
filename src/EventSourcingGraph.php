<?php

/**
 * @see       https://github.com/event-engine/php-inspectio-graph-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-inspectio-graph-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-inspectio-graph-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\InspectioGraphCody;

use EventEngine\InspectioGraph;
use EventEngine\InspectioGraph\VertexType;

final class EventSourcingGraph
{
    use InspectioGraph\EventSourcingGraph;

    /**
     * @var callable
     **/
    private $filterName;

    /**
     * @var callable
     */
    private $metadataFactory;

    public function __construct(
        callable $filterName,
        ?callable $metadataFactory = null
    ) {
        $this->filterName = $filterName;
        $this->metadataFactory = $metadataFactory;
    }

    public function analyseConnections(
        Node $node,
        InspectioGraph\VertexConnectionMap $vertexConnectionMap
    ): InspectioGraph\VertexConnectionMap {
        if (! $this->isTypeSupported($node)) {
            return $vertexConnectionMap;
        }

        $identity = Vertex::fromCodyNode($node, $this->filterName, $this->metadataFactory);

        foreach ($node->targets() as $target) {
            if (! $this->isTypeSupported($target)) {
                continue;
            }
            $targetIdentity = Vertex::fromCodyNode($target, $this->filterName, $this->metadataFactory);
            $vertexConnectionMap = $this->addConnection($identity, $targetIdentity, $vertexConnectionMap);
            $vertexConnectionMap = $this->addParent($target, $targetIdentity, $vertexConnectionMap);
        }

        foreach ($node->sources() as $source) {
            if (! $this->isTypeSupported($source)) {
                continue;
            }
            $sourceIdentity = Vertex::fromCodyNode($source, $this->filterName, $this->metadataFactory);
            $vertexConnectionMap = $this->addConnection($sourceIdentity, $identity, $vertexConnectionMap);
            $vertexConnectionMap = $this->addParent($source, $sourceIdentity, $vertexConnectionMap);
        }

        foreach ($node->children() as $child) {
            if (! $this->isTypeSupported($child)) {
                continue;
            }
            $vertexConnectionMap = $this->addParentConnection(
                Vertex::fromCodyNode($child, $this->filterName, $this->metadataFactory),
                $identity,
                $vertexConnectionMap
            );
        }

        return $this->addParent($node, $identity, $vertexConnectionMap);
    }

    public function removeConnection(
        Node $node,
        InspectioGraph\VertexConnectionMap $vertexConnectionMap
    ): InspectioGraph\VertexConnectionMap {
        if (! $this->isTypeSupported($node)) {
            return $vertexConnectionMap;
        }

        $identity = Vertex::fromCodyNode($node, $this->filterName, $this->metadataFactory);

        return $this->removeIdentity($identity, $vertexConnectionMap);
    }

    private function addParent(
        Node $node,
        VertexType $nodeIdentity,
        InspectioGraph\VertexConnectionMap $vertexConnectionMap
    ): InspectioGraph\VertexConnectionMap {
        if (($parent = $node->parent())
            && $this->isTypeSupported($parent)
        ) {
            $parentIdentity = Vertex::fromCodyNode($parent, $this->filterName, $this->metadataFactory);
            $vertexConnectionMap = $this->addParentConnection($nodeIdentity, $parentIdentity, $vertexConnectionMap);

            if (($parentParents = $parent->parent())
                && $parentParents->type() !== 'layer'
            ) {
                $vertexConnectionMap = $this->addParentConnection(
                    $parentIdentity,
                    Vertex::fromCodyNode($parentParents, $this->filterName, $this->metadataFactory),
                    $vertexConnectionMap
                );
            }
        }

        return $vertexConnectionMap;
    }

    private function isTypeSupported(Node $node): bool
    {
        $type = $node->type();

        return $type !== 'edge' && $type !== 'image' && $type !== 'layer' && $type !== 'freeText' && $type !== 'text' && $type !== 'icon';
    }
}
