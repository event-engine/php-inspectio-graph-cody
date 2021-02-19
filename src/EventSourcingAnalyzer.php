<?php

/**
 * @see       https://github.com/event-engine/php-inspectio-graph-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-inspectio-graph-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-inspectio-graph-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\InspectioGraphCody;

use EventEngine\InspectioGraph\AggregateConnection;
use EventEngine\InspectioGraph\AggregateConnectionMap;
use EventEngine\InspectioGraph\VertexMap;
use EventEngine\InspectioGraph\VertexType;
use EventEngine\InspectioGraphCody\Exception\RuntimeException;

final class EventSourcingAnalyzer implements \EventEngine\InspectioGraph\EventSourcingAnalyzer
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
    private $eventMap;

    /**
     * @var VertexMap
     */
    private $documentMap;

    /**
     * @var AggregateConnectionMap
     */
    private $aggregateConnectionMap;

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

        return $vertices;
    }

    /**
     * @param Node $node
     * @return Node[]
     */
    private function filterCommandsWithConnectionOf(Node $node): array
    {
        $vertices = [];

        switch ($this->node->type()) {
            case VertexType::TYPE_AGGREGATE:
                foreach ($this->node->sources() as $source) {
                    if ($source->type() === VertexType::TYPE_COMMAND) {
                        $vertices[] = $source;
                    }
                }
                break;
            case VertexType::TYPE_COMMAND:
                foreach ($this->node->targets() as $target) {
                    if ($this->areNodesEqual($target, $node)) {
                        $vertices[] = $this->node;
                    }
                }
                break;
            default:
                break;
        }

        return $vertices;
    }

    /**
     * @param Node $node
     * @return Node[]
     */
    private function filterEventsWithConnectionOf(Node $node): array
    {
        $vertices = [];

        switch ($this->node->type()) {
            case VertexType::TYPE_AGGREGATE:
                foreach ($this->node->targets() as $target) {
                    if ($target->type() === VertexType::TYPE_EVENT) {
                        $vertices[] = $target;
                    }
                }
                break;
            case VertexType::TYPE_EVENT:
                foreach ($this->node->sources() as $source) {
                    if ($this->areNodesEqual($source, $node)) {
                        $vertices[] = $this->node;
                    }
                }
                foreach ($this->node->targets() as $target) {
                    if ($this->areNodesEqual($target, $node)) {
                        $vertices[] = $this->node;
                    }
                }
                break;
            default:
                break;
        }

        return $vertices;
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

    public function aggregateMap(): AggregateConnectionMap
    {
        if (null === $this->aggregateConnectionMap) {
            $this->aggregateConnectionMap = AggregateConnectionMap::emptyMap();

            $commandMap = $this->commandMap();
            $eventMap = $this->eventMap();

            /** @var Node $aggregateVertex */
            foreach ($this->filterVerticesByType(VertexType::TYPE_AGGREGATE) as $aggregateVertex) {
                $aggregate = Vertex::fromCodyNode($aggregateVertex, $this->filterName, $this->metadataFactory);
                $name = $aggregate->name();

                if (true === $this->aggregateConnectionMap->has($name)) {
                    continue;
                }
                // @phpstan-ignore-next-line
                $aggregateConnection = new AggregateConnection($aggregate);

                $this->aggregateConnectionMap = $this->aggregateConnectionMap->with($name, $aggregateConnection);
                $commandVertices = $this->filterCommandsWithConnectionOf($aggregateVertex);
                $eventVertices = $this->filterEventsWithConnectionOf($aggregateVertex);

                $countCommandVertices = \count($commandVertices);

                if ($countCommandVertices > 1) {
                    throw new RuntimeException(
                        \sprintf('Multiple command connections to aggregate "%s" found. Can not handle it.', $name)
                    );
                }

                if ($countCommandVertices === 1) {
                    $command = Vertex::fromCodyNode(\current($commandVertices), $this->filterName);

                    if (true === $commandMap->has($command->name())) {
                        $events = [];

                        foreach ($eventVertices as $eventVertex) {
                            $event = Vertex::fromCodyNode($eventVertex, $this->filterName);

                            if ($eventMap->has($event->name())) {
                                $events[] = $eventMap->vertex($event->name());
                            }
                        }
                        // @phpstan-ignore-next-line
                        $aggregateConnection = $aggregateConnection->withCommandEvents($commandMap->vertex($command->name()), ...$events);
                    }
                } elseif (\count($eventVertices) > 0) {
                    foreach ($eventVertices as $eventVertex) {
                        $events = [];
                        $event = Vertex::fromCodyNode($eventVertex, $this->filterName);

                        if ($eventMap->has($event->name())) {
                            $events[] = $eventMap->vertex($event->name());
                        }
                    }
                    $aggregateConnection = $aggregateConnection->withEvents(...$events);
                }
                $this->aggregateConnectionMap = $this->aggregateConnectionMap->with($name, $aggregateConnection);
            }
        }

        return $this->aggregateConnectionMap;
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

    private function areNodesEqual(Node $a, Node $b): bool
    {
        return $a->name() === $b->name()
            && $a->type() === $b->type();
    }
}
