<?php

/**
 * @see       https://github.com/event-engine/php-inspectio-graph-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-inspectio-graph-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-inspectio-graph-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\InspectioGraphCody;

use EventEngine\InspectioGraph;
use EventEngine\InspectioGraph\Connection\AggregateConnectionAnalyzer;
use EventEngine\InspectioGraph\Connection\AggregateConnectionMap;
use EventEngine\InspectioGraph\Connection\FeatureConnectionAnalyzer;
use EventEngine\InspectioGraph\Connection\FeatureConnectionMap;
use EventEngine\InspectioGraph\VertexMap;
use EventEngine\InspectioGraph\VertexType;

final class EventSourcingAnalyzer implements InspectioGraph\EventSourcingAnalyzer, AggregateConnectionAnalyzer, FeatureConnectionAnalyzer
{
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
     * @var EventSourcingGraph
     */
    private $graph;

    public function __construct(EventSourcingGraph $graph)
    {
        $this->graph = $graph;

        $this->commandMap = VertexMap::emptyMap();
        $this->aggregateMap = VertexMap::emptyMap();
        $this->eventMap = VertexMap::emptyMap();
        $this->aggregateConnectionMap = AggregateConnectionMap::emptyMap();
        $this->documentMap = VertexMap::emptyMap();
        $this->policyMap = VertexMap::emptyMap();
        $this->uiMap = VertexMap::emptyMap();
        $this->externalSystemMap = VertexMap::emptyMap();
        $this->hotSpotMap = VertexMap::emptyMap();
        $this->featureMap = VertexMap::emptyMap();
        $this->featureConnectionMap = FeatureConnectionMap::emptyMap();
        $this->boundedContextMap = VertexMap::emptyMap();
    }

    /**
     * @param Node $node
     * @return VertexType The vertex type of the provided node from the corresponding map e.g. command map
     */
    public function analyse(Node $node): VertexType
    {
        // all maps can be analyzed in parallel
        $this->commandMap = $this->graph->analyseMap($node, $this->commandMap, VertexType::TYPE_COMMAND);
        $this->aggregateMap = $this->graph->analyseMap($node, $this->aggregateMap, VertexType::TYPE_AGGREGATE);
        $this->eventMap = $this->graph->analyseMap($node, $this->eventMap, VertexType::TYPE_EVENT);
        $this->documentMap = $this->graph->analyseMap($node, $this->documentMap, VertexType::TYPE_DOCUMENT);
        $this->policyMap = $this->graph->analyseMap($node, $this->policyMap, VertexType::TYPE_POLICY);
        $this->uiMap = $this->graph->analyseMap($node, $this->uiMap, VertexType::TYPE_UI);
        $this->externalSystemMap = $this->graph->analyseMap($node, $this->externalSystemMap, VertexType::TYPE_EXTERNAL_SYSTEM);
        $this->hotSpotMap = $this->graph->analyseMap($node, $this->hotSpotMap, VertexType::TYPE_HOT_SPOT);
        $this->featureMap = $this->graph->analyseMap($node, $this->featureMap, VertexType::TYPE_FEATURE);
        $this->boundedContextMap = $this->graph->analyseMap($node, $this->boundedContextMap, VertexType::TYPE_BOUNDED_CONTEXT);

        // all connection maps can be analyzed in parallel
        $this->aggregateConnectionMap = $this->graph->analyseAggregateConnectionMap($node, $this, $this->aggregateConnectionMap);
        $this->featureConnectionMap = $this->graph->analyseFeatureConnectionMap($node, $this, $this->featureConnectionMap);

        return $this->graph->vertexOfNode($node, $this);
    }

    public function commandMap(): VertexMap
    {
        return $this->commandMap;
    }

    public function eventMap(): VertexMap
    {
        return $this->eventMap;
    }

    public function aggregateMap(): VertexMap
    {
        return $this->aggregateMap;
    }

    public function aggregateConnectionMap(): AggregateConnectionMap
    {
        return $this->aggregateConnectionMap;
    }

    public function documentMap(): VertexMap
    {
        return $this->documentMap;
    }

    public function policyMap(): VertexMap
    {
        return $this->policyMap;
    }

    public function uiMap(): VertexMap
    {
        return $this->uiMap;
    }

    public function featureMap(): VertexMap
    {
        return $this->featureMap;
    }

    public function featureConnectionMap(): FeatureConnectionMap
    {
        return $this->featureConnectionMap;
    }

    public function boundedContextMap(): VertexMap
    {
        return $this->boundedContextMap;
    }

    public function externalSystemMap(): VertexMap
    {
        return $this->externalSystemMap;
    }

    public function hotSpotMap(): VertexMap
    {
        return $this->hotSpotMap;
    }
}
