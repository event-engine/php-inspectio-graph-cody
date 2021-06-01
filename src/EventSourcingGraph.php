<?php

/**
 * @see       https://github.com/event-engine/php-inspectio-graph-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-inspectio-graph-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-inspectio-graph-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\InspectioGraphCody;

use EventEngine\InspectioGraph;
use EventEngine\InspectioGraph\Connection\AggregateConnection;
use EventEngine\InspectioGraph\Connection\AggregateConnectionMap;
use EventEngine\InspectioGraph\Connection\FeatureConnection;
use EventEngine\InspectioGraph\Connection\FeatureConnectionMap;
use EventEngine\InspectioGraph\VertexMap;
use EventEngine\InspectioGraph\VertexType;
use EventEngine\InspectioGraphCody\Exception\RuntimeException;

final class EventSourcingGraph
{
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

    public function analyseMap(Node $node, VertexMap $map, string $vertexType): VertexMap
    {
        return $map->with(...$this->vertexMapByType($node, $vertexType));
    }

    public function vertexOfNode(Node $node, InspectioGraph\EventSourcingAnalyzer $analyzer): VertexType
    {
        switch ($node->type()) {
            case VertexType::TYPE_COMMAND:
                return $analyzer->commandMap()->vertex(($this->filterName)($node->name()));
            case VertexType::TYPE_EVENT:
                return $analyzer->eventMap()->vertex(($this->filterName)($node->name()));
            case VertexType::TYPE_AGGREGATE:
                return $analyzer->aggregateMap()->vertex(($this->filterName)($node->name()));
            case VertexType::TYPE_FEATURE:
                return $analyzer->featureMap()->vertex(($this->filterName)($node->name()));
            case VertexType::TYPE_DOCUMENT:
                return $analyzer->documentMap()->vertex(($this->filterName)($node->name()));
            case VertexType::TYPE_BOUNDED_CONTEXT:
                return $analyzer->boundedContextMap()->vertex(($this->filterName)($node->name()));
            case VertexType::TYPE_EXTERNAL_SYSTEM:
                return $analyzer->externalSystemMap()->vertex(($this->filterName)($node->name()));
            case VertexType::TYPE_HOT_SPOT:
                return $analyzer->hotSpotMap()->vertex(($this->filterName)($node->name()));
            case VertexType::TYPE_POLICY:
                return $analyzer->policyMap()->vertex(($this->filterName)($node->name()));
            case VertexType::TYPE_UI:
                return $analyzer->uiMap()->vertex(($this->filterName)($node->name()));
            default:
                throw new RuntimeException(\sprintf('Unknown vertex type "%s" provided.', $node->type()));
        }
    }

    public function analyseAggregateConnectionMap(
        Node $node,
        InspectioGraph\EventSourcingAnalyzer $analyzer,
        AggregateConnectionMap $aggregateConnectionMap
    ): AggregateConnectionMap {
        foreach ($this->filterVerticesByType($node, VertexType::TYPE_AGGREGATE) as $filteredNode) {
            $aggregateFromMap = $this->vertexFromMap($filteredNode, $analyzer);

            if ($aggregateFromMap === null) {
                throw new RuntimeException(
                    'Provided aggregate node was not found in aggregate map. Did you analyse the aggregate map before?'
                );
            }
            $aggregate = Vertex::fromCodyNode($filteredNode, $this->filterName, $this->metadataFactory);
            $name = $aggregate->name();

            // @phpstan-ignore-next-line
            $aggregateConnection = new AggregateConnection($aggregateFromMap);

            $commandVertices = $this->filterVertexTypeWithConnectionOf($node, VertexType::TYPE_COMMAND, $aggregate, $analyzer);
            $eventVertices = $this->filterVertexTypeWithConnectionOf($node, VertexType::TYPE_EVENT, $aggregate, $analyzer);
            $documentVertices = $this->filterVertexTypeWithConnectionOf($node, VertexType::TYPE_DOCUMENT, $aggregate, $analyzer);

            $countCommandVertices = \count($commandVertices);

            if ($countCommandVertices > 1) {
                throw new RuntimeException(
                    \sprintf('Multiple command connections to aggregate "%s" found. Can not handle it.', $name)
                );
            }

            if ($countCommandVertices === 1) {
                $command = $commandVertices->current();
                // @phpstan-ignore-next-line
                $aggregateConnection = $aggregateConnection->withCommandEvents($command, ...$eventVertices->vertices());
            } elseif (\count($eventVertices) > 0) {
                // @phpstan-ignore-next-line
                $aggregateConnection = $aggregateConnection->withEvents(...$eventVertices->vertices());
            }

            if (\count($documentVertices) > 0) {
                $aggregateConnection = $aggregateConnection->withDocuments(...$documentVertices->vertices());
            }

            $aggregateConnectionMap = $aggregateConnectionMap->with($aggregate->id(), $aggregateConnection);
        }
        // @phpstan-ignore-next-line
        return $aggregateConnectionMap;
    }

    public function analyseFeatureConnectionMap(
        Node $node,
        InspectioGraph\EventSourcingAnalyzer $analyzer,
        FeatureConnectionMap $featureConnectionMap
    ): FeatureConnectionMap {
        foreach ($this->filterVerticesByType($node, VertexType::TYPE_FEATURE) as $filteredNode) {
            $featureFromMap = $this->vertexFromMap($filteredNode, $analyzer);

            if ($featureFromMap === null) {
                throw new RuntimeException(
                    'Provided feature node was not found in feature map. Did you analyse the feature map before?'
                );
            }

            $feature = Vertex::fromCodyNode($filteredNode, $this->filterName, $this->metadataFactory);

            // @phpstan-ignore-next-line
            $featureConnection = new FeatureConnection($featureFromMap);

            $featureConnection = $featureConnection
                ->withCommands(
                    ...$this->filterVertexTypeWithConnectionOf($node, VertexType::TYPE_COMMAND, $feature, $analyzer)->vertices()
                )
                ->withEvents(
                    ...$this->filterVertexTypeWithConnectionOf($node, VertexType::TYPE_EVENT, $feature, $analyzer)->vertices()
                )
                ->withAggregates(
                    ...$this->filterVertexTypeWithConnectionOf($node, VertexType::TYPE_AGGREGATE, $feature, $analyzer)->vertices()
                )
                ->withDocuments(
                    ...$this->filterVertexTypeWithConnectionOf($node, VertexType::TYPE_DOCUMENT, $feature, $analyzer)->vertices()
                )
                ->withExternalSystems(
                    ...$this->filterVertexTypeWithConnectionOf($node, VertexType::TYPE_EXTERNAL_SYSTEM, $feature, $analyzer)->vertices()
                )
                ->withHotSpots(
                    ...$this->filterVertexTypeWithConnectionOf($node, VertexType::TYPE_HOT_SPOT, $feature, $analyzer)->vertices()
                )
                ->withPolicies(
                    ...$this->filterVertexTypeWithConnectionOf($node, VertexType::TYPE_POLICY, $feature, $analyzer)->vertices()
                )
                ->withUis(
                    ...$this->filterVertexTypeWithConnectionOf($node, VertexType::TYPE_UI, $feature, $analyzer)->vertices()
                );

            $featureConnectionMap = $featureConnectionMap->with($feature->id(), $featureConnection);
        }

        // @phpstan-ignore-next-line
        return $featureConnectionMap;
    }

    /**
     * @return Node[]
     */
    private function filterVerticesByType(Node $node, string $type): array
    {
        $vertices = [];

        if ($node->type() === $type) {
            $vertices[] = $node;
        }

        $parent = $node->parent();

        if ($parent && $parent->type() === $type) {
            $vertices[] = $parent;
        }

        foreach ($node->sources() as $source) {
            if ($source->type() === $type) {
                $vertices[] = $source;
            }
        }

        foreach ($node->targets() as $target) {
            if ($target->type() === $type) {
                $vertices[] = $target;
            }
        }

        foreach ($node->children() as $child) {
            if ($child->type() === $type) {
                $vertices[] = $child;
            }
        }

        return $vertices;
    }

    private function filterVertexTypeWithConnectionOf(
        Node $node,
        string $vertexType,
        VertexType $connectionNode,
        InspectioGraph\EventSourcingAnalyzer $analyzer
    ): VertexMap {
        $vertices = VertexMap::emptyMap();

        switch ($node->type()) {
            case VertexType::TYPE_AGGREGATE:
                foreach ($node->sources() as $source) {
                    if ($source->type() === $vertexType
                        && ($vertex = $this->vertexFromMap($source, $analyzer))
                    ) {
                        $vertices = $vertices->with($vertex);
                    }
                }
                foreach ($node->targets() as $target) {
                    if ($target->type() === $vertexType
                        && ($vertex = $this->vertexFromMap($target, $analyzer))
                    ) {
                        $vertices = $vertices->with($vertex);
                    }
                }
                $parent = $node->parent();

                if (null !== $parent
                    && $node->type() === $vertexType
                    && $this->areNodesEqual($parent, $connectionNode)
                ) {
                    $vertices = $vertices->with($this->vertexFromMap($node, $analyzer));
                }
                break;
            case VertexType::TYPE_COMMAND:
            case VertexType::TYPE_EVENT:
            case VertexType::TYPE_DOCUMENT:
                foreach ($node->sources() as $source) {
                    if ($node->type() === $vertexType
                        && $this->areNodesEqual($source, $connectionNode)
                    ) {
                        $vertices = $vertices->with($this->vertexFromMap($node, $analyzer));
                    } elseif ($parent = $source->parent()) {
                        if ($source->type() === $vertexType
                            && $this->areNodesEqual($parent, $connectionNode)
                        ) {
                            $vertices = $vertices->with($this->vertexFromMap($source, $analyzer));
                        }
                    }
                }
                foreach ($node->targets() as $target) {
                    if ($node->type() === $vertexType
                        && $this->areNodesEqual($target, $connectionNode)
                    ) {
                        $vertices = $vertices->with($this->vertexFromMap($node, $analyzer));
                    } elseif ($parent = $target->parent()) {
                        if ($target->type() === $vertexType
                            && $this->areNodesEqual($parent, $connectionNode)
                        ) {
                            $vertices = $vertices->with($this->vertexFromMap($target, $analyzer));
                        }
                    }
                }
                $parent = $node->parent();

                if (null !== $parent
                    && $node->type() === $vertexType
                    && $this->areNodesEqual($parent, $connectionNode)
                ) {
                    $vertices = $vertices->with($this->vertexFromMap($node, $analyzer));
                }
                break;
            case VertexType::TYPE_FEATURE:
                foreach ($node->children() as $child) {
                    if ($child->type() === $vertexType
                        && ($vertex = $this->vertexFromMap($child, $analyzer))
                    ) {
                        if ($connectionNode instanceof InspectioGraph\FeatureType) {
                            $vertices = $vertices->with($vertex);
                        } elseif ($this->isFeatureVertexSourceOrTargetOf($node, $vertex, $connectionNode)) {
                            $vertices = $vertices->with($vertex);
                        }
                    }
                }
                break;
            default:
                break;
        }

        return $vertices;
    }

    private function isFeatureVertexSourceOrTargetOf(
        Node $node,
        VertexType $vertexType,
        VertexType $connectionNode
    ): bool {
        foreach ($node->children() as $child) {
            if ($child->type() === 'edge'
                || $this->areNodesIdentical($child, $connectionNode) === false
            ) {
                continue;
            }

            foreach ($child->sources() as $childSource) {
                if ($this->areNodesIdentical($childSource, $vertexType)) {
                    return true;
                }
            }
            foreach ($child->targets() as $childTarget) {
                if ($this->areNodesIdentical($childTarget, $vertexType)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function vertexMapByType(Node $node, string $type): array
    {
        return \array_map(
            function (Node $vertex) {
                return Vertex::fromCodyNode($vertex, $this->filterName, $this->metadataFactory);
            },
            $this->filterVerticesByType($node, $type)
        );
    }

    private function vertexFromMap(Node $node, InspectioGraph\EventSourcingAnalyzer $analyzer): ?VertexType
    {
        $name = ($this->filterName)($node->name());

        switch ($node->type()) {
            case VertexType::TYPE_COMMAND:
                if ($analyzer->commandMap()->has($name)) {
                    return $analyzer->commandMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_AGGREGATE:
                if ($analyzer->aggregateMap()->has($name)) {
                    return $analyzer->aggregateMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_EVENT:
                if ($analyzer->eventMap()->has($name)) {
                    return $analyzer->eventMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_DOCUMENT:
                if ($analyzer->documentMap()->has($name)) {
                    return $analyzer->documentMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_POLICY:
                if ($analyzer->policyMap()->has($name)) {
                    return $analyzer->policyMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_UI:
                if ($analyzer->uiMap()->has($name)) {
                    return $analyzer->uiMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_HOT_SPOT:
                if ($analyzer->hotSpotMap()->has($name)) {
                    return $analyzer->hotSpotMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_FEATURE:
                if ($analyzer->featureMap()->has($name)) {
                    return $analyzer->featureMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_BOUNDED_CONTEXT:
                if ($analyzer->boundedContextMap()->has($name)) {
                    return $analyzer->boundedContextMap()->vertex($name);
                }

                return null;
            default:
                throw new RuntimeException(
                    \sprintf('Type "%s" is not supported', $node->type())
                );
        }
    }

    private function areNodesEqual(Node $a, VertexType $b): bool
    {
        $nodeName = ($this->filterName)($a->name());

        return $nodeName === $b->name()
            && $a->type() === $b->type();
    }

    private function areNodesIdentical(Node $a, VertexType $b): bool
    {
        return $a->type() === $b->type()
            && $a->id() === $b->id();
    }
}
