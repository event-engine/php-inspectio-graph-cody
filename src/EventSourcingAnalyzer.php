<?php

/**
 * @see       https://github.com/event-engine/php-inspectio-graph-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-inspectio-graph-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-inspectio-graph-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\InspectioGraphCody;

use Countable;
use EventEngine\InspectioGraph;
use EventEngine\InspectioGraph\VertexConnection;
use EventEngine\InspectioGraph\VertexConnectionMap;
use EventEngine\InspectioGraph\VertexType;
use Iterator;

final class EventSourcingAnalyzer implements InspectioGraph\EventSourcingAnalyzer, Countable, Iterator
{
    use InspectioGraph\EventSourcingGraph;

    private VertexConnectionMap $identityConnectionMap;
    private EventSourcingGraph $graph;

    public function __construct(EventSourcingGraph $graph)
    {
        $this->graph = $graph;
        $this->identityConnectionMap = VertexConnectionMap::emptyMap();
    }

    /**
     * @param Node $node
     * @return InspectioGraph\VertexConnection|null The vertex with it's connections of provided node or null if not supported
     */
    public function analyse(Node $node): ?InspectioGraph\VertexConnection
    {
        $this->identityConnectionMap = $this->graph->analyseConnections($node, $this->identityConnectionMap);

        return $this->identityConnectionMap->has($node->id())
            ? $this->identityConnectionMap->connection($node->id())
            : null;
    }

    public function remove(Node $node): void
    {
        $this->identityConnectionMap = $this->graph->removeConnection($node, $this->identityConnectionMap);
    }

    public function commandMap(): VertexConnectionMap
    {
        return $this->identityConnectionMap->filterByType(VertexType::TYPE_COMMAND);
    }

    public function eventMap(): VertexConnectionMap
    {
        return $this->identityConnectionMap->filterByType(VertexType::TYPE_EVENT);
    }

    public function aggregateMap(): VertexConnectionMap
    {
        return $this->identityConnectionMap->filterByType(VertexType::TYPE_AGGREGATE);
    }

    public function documentMap(): VertexConnectionMap
    {
        return $this->identityConnectionMap->filterByType(VertexType::TYPE_DOCUMENT);
    }

    public function policyMap(): VertexConnectionMap
    {
        return $this->identityConnectionMap->filterByType(VertexType::TYPE_POLICY);
    }

    public function uiMap(): VertexConnectionMap
    {
        return $this->identityConnectionMap->filterByType(VertexType::TYPE_UI);
    }

    public function featureMap(): VertexConnectionMap
    {
        return $this->identityConnectionMap->filterByType(VertexType::TYPE_FEATURE);
    }

    public function boundedContextMap(): VertexConnectionMap
    {
        return $this->identityConnectionMap->filterByType(VertexType::TYPE_BOUNDED_CONTEXT);
    }

    public function externalSystemMap(): VertexConnectionMap
    {
        return $this->identityConnectionMap->filterByType(VertexType::TYPE_EXTERNAL_SYSTEM);
    }

    public function hotSpotMap(): VertexConnectionMap
    {
        return $this->identityConnectionMap->filterByType(VertexType::TYPE_HOT_SPOT);
    }

    public function graph(): VertexConnectionMap
    {
        return $this->identityConnectionMap;
    }

    public function clearGraph(): void
    {
        $this->identityConnectionMap = VertexConnectionMap::emptyMap();
    }

    public function has(string $id): bool
    {
        return $this->identityConnectionMap->has($id);
    }

    public function connection(string $id): VertexConnection
    {
        return $this->identityConnectionMap->connection($id);
    }

    public function count(): int
    {
        return \count($this->identityConnectionMap);
    }

    public function rewind(): void
    {
        $this->identityConnectionMap->rewind();
    }

    public function key(): string
    {
        return $this->identityConnectionMap->key();
    }

    public function next(): void
    {
        $this->identityConnectionMap->next();
    }

    public function valid(): bool
    {
        return $this->identityConnectionMap->valid();
    }

    /**
     * @return VertexConnection|false|mixed
     */
    public function current()
    {
        return $this->identityConnectionMap->current();
    }
}
