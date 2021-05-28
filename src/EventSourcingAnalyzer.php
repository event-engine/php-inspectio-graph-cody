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
use EventEngine\InspectioGraph\Connection\AggregateConnectionAnalyzer;
use EventEngine\InspectioGraph\Connection\AggregateConnectionMap;
use EventEngine\InspectioGraph\Connection\FeatureConnection;
use EventEngine\InspectioGraph\Connection\FeatureConnectionAnalyzer;
use EventEngine\InspectioGraph\Connection\FeatureConnectionMap;
use EventEngine\InspectioGraph\VertexMap;
use EventEngine\InspectioGraph\VertexType;
use EventEngine\InspectioGraphCody\Exception\RuntimeException;

final class EventSourcingAnalyzer implements InspectioGraph\EventSourcingAnalyzer, AggregateConnectionAnalyzer, FeatureConnectionAnalyzer
{
    /**
     * @var Node
     **/
    private $node;

    /**
     * @var callable
     **/
    private $filterName;

    /**
     * @var VertexMap
     */
    private $commandMap;

    /**
     * @var VertexMap
     */
    private $aggregateMap;

    /**
     * @var AggregateConnectionMap
     */
    private $aggregateConnectionMap;

    /**
     * @var VertexMap
     */
    private $eventMap;

    /**
     * @var VertexMap
     */
    private $documentMap;

    /**
     * @var VertexMap
     */
    private $policyMap;

    /**
     * @var VertexMap
     */
    private $uiMap;

    /**
     * @var VertexMap
     */
    private $externalSystemMap;

    /**
     * @var VertexMap
     */
    private $hotSpotMap;

    /**
     * @var VertexMap
     */
    private $featureMap;

    /**
     * @var FeatureConnectionMap
     */
    private $featureConnectionMap;

    /**
     * @var VertexMap
     */
    private $boundedContextMap;

    /**
     * @var callable
     */
    private $metadataFactory;

    public function __construct(
        Node $node,
        callable $filterName,
        ?callable $metadataFactory = null
    ) {
        $this->node = $node;
        $this->filterName = $filterName;
        $this->metadataFactory = $metadataFactory;

        $this->analyse($node); // for BC, $node should be removed later
    }

    public function analyse(Node $node): void
    {
        $this->node = $node;

        // all maps can be analyzed in parallel
        $this->commandMap = $this->commandMap()->with(...$this->vertexMapByType(VertexType::TYPE_COMMAND));
        $this->aggregateMap = $this->aggregateMap()->with(...$this->vertexMapByType(VertexType::TYPE_AGGREGATE));
        $this->eventMap = $this->eventMap()->with(...$this->vertexMapByType(VertexType::TYPE_EVENT));
        $this->documentMap = $this->documentMap()->with(...$this->vertexMapByType(VertexType::TYPE_DOCUMENT));
        $this->policyMap = $this->policyMap()->with(...$this->vertexMapByType(VertexType::TYPE_POLICY));
        $this->uiMap = $this->uiMap()->with(...$this->vertexMapByType(VertexType::TYPE_UI));
        $this->externalSystemMap = $this->externalSystemMap()->with(...$this->vertexMapByType(VertexType::TYPE_EXTERNAL_SYSTEM));
        $this->hotSpotMap = $this->hotSpotMap()->with(...$this->vertexMapByType(VertexType::TYPE_HOT_SPOT));
        $this->featureMap = $this->featureMap()->with(...$this->vertexMapByType(VertexType::TYPE_FEATURE));
        $this->boundedContextMap = $this->boundedContextMap()->with(...$this->boundedContextMap()->vertices());

        // all connection maps can be analyzed in parallel
        foreach ($this->determineAggregateConnection() as $aggregateConnection) {
            $this->aggregateConnectionMap = $this->aggregateConnectionMap()->with(
                $aggregateConnection->aggregate()->id(),
                $aggregateConnection
            );
        }

        foreach ($this->determineFeatureConnectionMap() as $featureConnection) {
            $this->featureConnectionMap = $this->featureConnectionMap()->with(
                $featureConnection->feature()->id(),
                $featureConnection
            );
        }

        $this->commandMap()->rewind();
        $this->aggregateMap()->rewind();
        $this->eventMap()->rewind();
        $this->aggregateConnectionMap()->rewind();
        $this->documentMap()->rewind();
        $this->policyMap()->rewind();
        $this->uiMap()->rewind();
        $this->externalSystemMap()->rewind();
        $this->hotSpotMap()->rewind();
        $this->featureMap()->rewind();
        $this->featureConnectionMap()->rewind();
        $this->boundedContextMap()->rewind();
    }

    /**
     * @param string $type
     * @return Node[]
     */
    private function filterVerticesByType(string $type): array
    {
        $vertices = [];

        if ($this->node->type() === $type) {
            $vertices[] = $this->node;
        }

        $parent = $this->node->parent();

        if ($parent && $parent->type() === $type) {
            $vertices[] = $parent;
        }

        foreach ($this->node->sources() as $source) {
            if ($source->type() === $type) {
                $vertices[] = $source;
            }
        }

        foreach ($this->node->targets() as $target) {
            if ($target->type() === $type) {
                $vertices[] = $target;
            }
        }

        foreach ($this->node->children() as $child) {
            if ($child->type() === $type) {
                $vertices[] = $child;
            }
        }

        return $vertices;
    }

    /**
     * @param string $vertexType
     * @param VertexType $node
     * @return VertexMap
     */
    private function filterVertexTypeWithConnectionOf(string $vertexType, VertexType $node): VertexMap
    {
        $vertices = VertexMap::emptyMap();

        switch ($this->node->type()) {
            case VertexType::TYPE_AGGREGATE:
                foreach ($this->node->sources() as $source) {
                    if ($source->type() === $vertexType
                        && ($vertex = $this->vertexFromMap($source))
                    ) {
                        $vertices = $vertices->with($vertex);
                    }
                }
                foreach ($this->node->targets() as $target) {
                    if ($target->type() === $vertexType
                        && ($vertex = $this->vertexFromMap($target))
                    ) {
                        $vertices = $vertices->with($vertex);
                    }
                }
                $parent = $this->node->parent();

                if (null !== $parent
                    && $this->node->type() === $vertexType
                    && $this->areNodesEqual($parent, $node)
                ) {
                    $vertices = $vertices->with($this->vertexFromMap($this->node));
                }
                break;
            case VertexType::TYPE_COMMAND:
            case VertexType::TYPE_EVENT:
            case VertexType::TYPE_DOCUMENT:
                foreach ($this->node->sources() as $source) {
                    if ($this->node->type() === $vertexType
                        && $this->areNodesEqual($source, $node)
                    ) {
                        $vertices = $vertices->with($this->vertexFromMap($this->node));
                    } elseif ($parent = $source->parent()) {
                        if ($source->type() === $vertexType
                            && $this->areNodesEqual($parent, $node)
                        ) {
                            $vertices = $vertices->with($this->vertexFromMap($source));
                        }
                    }
                }
                foreach ($this->node->targets() as $target) {
                    if ($this->node->type() === $vertexType
                        && $this->areNodesEqual($target, $node)
                    ) {
                        $vertices = $vertices->with($this->vertexFromMap($this->node));
                    } elseif ($parent = $target->parent()) {
                        if ($target->type() === $vertexType
                            && $this->areNodesEqual($parent, $node)
                        ) {
                            $vertices = $vertices->with($this->vertexFromMap($target));
                        }
                    }
                }
                $parent = $this->node->parent();

                if (null !== $parent
                    && $this->node->type() === $vertexType
                    && $this->areNodesEqual($parent, $node)
                ) {
                    $vertices = $vertices->with($this->vertexFromMap($this->node));
                }
                break;
            case VertexType::TYPE_FEATURE:
                foreach ($this->node->children() as $child) {
                    if ($child->type() === $vertexType
                        && ($vertex = $this->vertexFromMap($child))
                    ) {
                        if ($node instanceof InspectioGraph\FeatureType) {
                            $vertices = $vertices->with($vertex);
                        } elseif ($this->isFeatureVertexSourceOrTargetOf($vertex, $node)) {
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

    private function isFeatureVertexSourceOrTargetOf(VertexType $vertexType, VertexType $node): bool
    {
        foreach ($this->node->children() as $child) {
            if ($child->type() === 'edge'
                || $this->areNodesIdentical($child, $node) === false
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

    public function commandMap(): VertexMap
    {
        if (null === $this->commandMap) {
            $this->commandMap = VertexMap::fromVertices(...$this->vertexMapByType(VertexType::TYPE_COMMAND));
        }

        return $this->commandMap;
    }

    public function eventMap(): VertexMap
    {
        if (null === $this->eventMap) {
            $this->eventMap = VertexMap::fromVertices(...$this->vertexMapByType(VertexType::TYPE_EVENT));
        }

        return $this->eventMap;
    }

    public function aggregateMap(): VertexMap
    {
        if (null === $this->aggregateMap) {
            $this->aggregateMap = VertexMap::fromVertices(...$this->vertexMapByType(VertexType::TYPE_AGGREGATE));
        }

        return $this->aggregateMap;
    }

    public function aggregateConnectionMap(): AggregateConnectionMap
    {
        if (null === $this->aggregateConnectionMap) {
            $this->aggregateConnectionMap = AggregateConnectionMap::emptyMap();

            foreach ($this->determineAggregateConnection() as $aggregateConnection) {
                $this->aggregateConnectionMap = $this->aggregateConnectionMap->with(
                    $aggregateConnection->aggregate()->id(),
                    $aggregateConnection
                );
            }
        }

        return $this->aggregateConnectionMap;
    }

    private function determineAggregateConnection(): AggregateConnectionMap
    {
        $aggregateConnectionMap = AggregateConnectionMap::emptyMap();

        foreach ($this->filterVerticesByType(VertexType::TYPE_AGGREGATE) as $node) {
            $aggregate = Vertex::fromCodyNode($node, $this->filterName, $this->metadataFactory);
            $name = $aggregate->name();

            // @phpstan-ignore-next-line
            $aggregateConnection = new AggregateConnection($aggregate);

            $aggregateConnectionMap = $aggregateConnectionMap->with($aggregate->id(), $aggregateConnection);
            $commandVertices = $this->filterVertexTypeWithConnectionOf(VertexType::TYPE_COMMAND, $aggregate);
            $eventVertices = $this->filterVertexTypeWithConnectionOf(VertexType::TYPE_EVENT, $aggregate);
            $documentVertices = $this->filterVertexTypeWithConnectionOf(VertexType::TYPE_DOCUMENT, $aggregate);

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

        return $aggregateConnectionMap;
    }

    public function documentMap(): VertexMap
    {
        if (null === $this->documentMap) {
            $this->documentMap = VertexMap::fromVertices(...$this->vertexMapByType(VertexType::TYPE_DOCUMENT));
        }

        return $this->documentMap;
    }

    private function vertexMapByType(string $type): array
    {
        return \array_map(
            function (Node $vertex) {
                return Vertex::fromCodyNode($vertex, $this->filterName, $this->metadataFactory);
            },
            $this->filterVerticesByType($type)
        );
    }

    private function vertexFromMap(Node $node): ?VertexType
    {
        $name = ($this->filterName)($node->name());

        switch ($node->type()) {
            case VertexType::TYPE_COMMAND:
                if ($this->commandMap()->has($name)) {
                    return $this->commandMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_AGGREGATE:
                if ($this->aggregateMap()->has($name)) {
                    return $this->aggregateMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_EVENT:
                if ($this->eventMap()->has($name)) {
                    return $this->eventMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_DOCUMENT:
                if ($this->documentMap()->has($name)) {
                    return $this->documentMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_POLICY:
                if ($this->policyMap()->has($name)) {
                    return $this->policyMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_UI:
                if ($this->uiMap()->has($name)) {
                    return $this->uiMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_HOT_SPOT:
                if ($this->hotSpotMap()->has($name)) {
                    return $this->hotSpotMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_FEATURE:
                if ($this->featureMap()->has($name)) {
                    return $this->featureMap()->vertex($name);
                }

                return null;
            case VertexType::TYPE_BOUNDED_CONTEXT:
                if ($this->boundedContextMap()->has($name)) {
                    return $this->boundedContextMap()->vertex($name);
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

    public function policyMap(): VertexMap
    {
        if (null === $this->policyMap) {
            $this->policyMap = VertexMap::fromVertices(...$this->vertexMapByType(VertexType::TYPE_POLICY));
        }

        return $this->policyMap;
    }

    public function uiMap(): VertexMap
    {
        if (null === $this->uiMap) {
            $this->uiMap = VertexMap::fromVertices(...$this->vertexMapByType(VertexType::TYPE_UI));
        }

        return $this->uiMap;
    }

    public function featureMap(): VertexMap
    {
        if (null === $this->featureMap) {
            $this->featureMap = VertexMap::fromVertices(...$this->vertexMapByType(VertexType::TYPE_FEATURE));
        }

        return $this->featureMap;
    }

    public function featureConnectionMap(): FeatureConnectionMap
    {
        if (null === $this->featureConnectionMap) {
            $this->featureConnectionMap = FeatureConnectionMap::emptyMap();

            foreach ($this->determineFeatureConnectionMap() as $featureConnection) {
                $this->featureConnectionMap = $this->featureConnectionMap->with(
                    $featureConnection->feature()->id(),
                    $featureConnection
                );
            }
        }

        return $this->featureConnectionMap;
    }

    private function determineFeatureConnectionMap(): FeatureConnectionMap
    {
        $featureConnectionMap = FeatureConnectionMap::emptyMap();

        foreach ($this->filterVerticesByType(VertexType::TYPE_FEATURE) as $node) {
            $feature = Vertex::fromCodyNode($node, $this->filterName, $this->metadataFactory);

            // @phpstan-ignore-next-line
            $featureConnection = new FeatureConnection($feature);

            $featureConnectionMap = $featureConnectionMap->with($feature->id(), $featureConnection);

            $featureConnection = $featureConnection
                ->withCommands(...$this->filterVertexTypeWithConnectionOf(VertexType::TYPE_COMMAND, $feature)->vertices())
                ->withEvents(...$this->filterVertexTypeWithConnectionOf(VertexType::TYPE_EVENT, $feature)->vertices())
                ->withAggregates(...$this->filterVertexTypeWithConnectionOf(VertexType::TYPE_AGGREGATE, $feature)->vertices())
                ->withDocuments(...$this->filterVertexTypeWithConnectionOf(VertexType::TYPE_DOCUMENT, $feature)->vertices())
                ->withExternalSystems(...$this->filterVertexTypeWithConnectionOf(VertexType::TYPE_EXTERNAL_SYSTEM, $feature)->vertices())
                ->withHotSpots(...$this->filterVertexTypeWithConnectionOf(VertexType::TYPE_HOT_SPOT, $feature)->vertices())
                ->withPolicies(...$this->filterVertexTypeWithConnectionOf(VertexType::TYPE_POLICY, $feature)->vertices())
                ->withUis(...$this->filterVertexTypeWithConnectionOf(VertexType::TYPE_UI, $feature)->vertices());

            $featureConnectionMap = $featureConnectionMap->with($feature->id(), $featureConnection);
        }

        return $featureConnectionMap;
    }

    public function boundedContextMap(): VertexMap
    {
        if (null === $this->boundedContextMap) {
            $this->boundedContextMap = VertexMap::fromVertices(...$this->vertexMapByType(VertexType::TYPE_BOUNDED_CONTEXT));
        }

        return $this->boundedContextMap;
    }

    public function externalSystemMap(): VertexMap
    {
        if (null === $this->externalSystemMap) {
            $this->externalSystemMap = VertexMap::fromVertices(...$this->vertexMapByType(VertexType::TYPE_EXTERNAL_SYSTEM));
        }

        return $this->externalSystemMap;
    }

    public function hotSpotMap(): VertexMap
    {
        if (null === $this->hotSpotMap) {
            $this->hotSpotMap = VertexMap::fromVertices(...$this->vertexMapByType(VertexType::TYPE_HOT_SPOT));
        }

        return $this->hotSpotMap;
    }
}
