<?php

/**
 * @see       https://github.com/event-engine/php-inspectio-graph-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-inspectio-graph-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-inspectio-graph-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\InspectioGraphCody;

use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\Connection\AggregateConnection;
use EventEngine\InspectioGraph\DocumentType;
use EventEngine\InspectioGraph\EventType;
use EventEngine\InspectioGraph\VertexMap;
use EventEngine\InspectioGraphCody\EventSourcingAnalyzer;
use EventEngine\InspectioGraphCody\JsonNode;
use PHPUnit\Framework\TestCase;

final class EventSourcingAnalyzerTest extends TestCase
{
    private const FILES_DIR = __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR;

    /**
     * @var callable
     */
    private $filter;

    public function setUp(): void
    {
        $this->filter = static function (string $value) {
            return \trim($value);
        };
    }

    /**
     * @test
     */
    public function it_returns_command_map_of_command_node(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'add_building.json'));

        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);
        $commandMap = $eventSourcingAnalyzer->commandMap();

        $this->assertCount(1, $commandMap);
        $command = $commandMap->current();

        $this->assertCommandAddBuilding($command, '9bJ5Y7yuBcfWyei7i2ZSDC');
    }

    /**
     * @test
     */
    public function it_returns_feature_connection_map_of_command_node(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'add_building.json'));

        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);
        $featureConnectionMap = $eventSourcingAnalyzer->featureConnectionMap();

        $this->assertCount(1, $featureConnectionMap);
        $featureConnection = $featureConnectionMap->current();

        $this->assertCommandAddBuilding($featureConnection->commandMap()->current(), '9bJ5Y7yuBcfWyei7i2ZSDC');
        $this->assertAggregateBuilding($featureConnection->aggregateMap()->current(), 'buTwEKKNLBBo6WAERYN1Gn');
    }

    /**
     * @test
     */
    public function it_returns_aggregate_connection_map_of_command_node(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'add_building.json'));

        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);
        $aggregateConnectionMap = $eventSourcingAnalyzer->aggregateConnectionMap();

        $this->assertCount(1, $aggregateConnectionMap);

        $this->assertAggregateConnectionMapOfCommandAddBuilding($aggregateConnectionMap->current());
    }

    private function assertAggregateConnectionMapOfCommandAddBuilding(
        AggregateConnection $aggregateConnection,
        bool $withEvent = false): void
    {
        $this->assertAggregateBuilding($aggregateConnection->aggregate(), 'buTwEKKNLBBo6WAERYN1Gn');

        $commandMap = $aggregateConnection->commandMap();
        $this->assertCount(1, $commandMap);
        $this->assertCommandAddBuilding($commandMap->current(), '9bJ5Y7yuBcfWyei7i2ZSDC');

        $eventMap = $aggregateConnection->eventMap();

        if ($withEvent === false) {
            $this->assertCount(0, $eventMap);
        } else {
            $this->assertCount(1, $eventMap);
            $this->assertEventBuildingAdded($eventMap->current());
        }
    }

    private function assertAggregateConnectionMapOfCommandCheckInUser(AggregateConnection $aggregateConnection): void
    {
        $this->assertAggregateBuilding($aggregateConnection->aggregate(), 'eiaS8gtsBemMReTNbeNRXj');

        $commandMap = $aggregateConnection->commandMap();
        $this->assertCount(1, $commandMap);
        $this->assertCommandCheckInUser($commandMap->current());

        $eventMap = $aggregateConnection->eventMap();
        $eventMap->rewind();
        $this->assertCount(2, $eventMap);
        $this->assertEventUserCheckedIn($eventMap->current());

        $eventMap->next();
        $this->assertEventDoubleCheckInDetected($eventMap->current());
    }

    private function assertAggregateConnectionMapOfCommandCheckOutUser(AggregateConnection $aggregateConnection): void
    {
        $this->assertAggregateBuilding($aggregateConnection->aggregate(), 'jKrpwfkdZnT5xMRKMYrgTF');

        $commandMap = $aggregateConnection->commandMap();
        $this->assertCount(1, $commandMap);
        $this->assertCommandCheckOutUser($commandMap->current());

        $eventMap = $aggregateConnection->eventMap();
        $eventMap->rewind();
        $this->assertCount(1, $eventMap);
        $this->assertEventUserCheckedOut($eventMap->current());
    }

    /**
     * @test
     */
    public function it_returns_aggregate_connection_map_of_event_node(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_added.json'));

        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);
        $aggregateMap = $eventSourcingAnalyzer->aggregateConnectionMap();

        $this->assertCount(1, $aggregateMap);
        $aggregate = $aggregateMap->current();
        $this->assertAggregateBuilding($aggregate->aggregate(), 'buTwEKKNLBBo6WAERYN1Gn');

        $eventMap = $aggregate->eventMap();
        $this->assertCount(1, $eventMap);
        $this->assertEventBuildingAdded($eventMap->current());

        $commandMap = $aggregate->commandMap();
        $this->assertCount(0, $commandMap);
    }

    /**
     * @test
     */
    public function it_returns_aggregate_connection_map_of_document_node(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'name_vo.json'));

        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);
        $aggregateMap = $eventSourcingAnalyzer->aggregateConnectionMap();

        $this->assertCount(1, $aggregateMap);
        $aggregate = $aggregateMap->current();
        $this->assertAggregateBuilding($aggregate->aggregate(), 'buTwEKKNLBBo6WAERYN1Gn');

        $documentMap = $aggregate->documentMap();
        $this->assertCount(1, $documentMap);
        $this->assertDocumentName($documentMap->current());

        $commandMap = $aggregate->commandMap();
        $this->assertCount(0, $commandMap);

        $eventMap = $aggregate->eventMap();
        $this->assertCount(0, $eventMap);
    }

    /**
     * @test
     */
    public function it_returns_feature_connection_map_of_event_node(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_added.json'));

        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);
        $featureConnectionMap = $eventSourcingAnalyzer->featureConnectionMap();

        $this->assertCount(1, $featureConnectionMap);
        $featureConnection = $featureConnectionMap->current();

        $aggregateMap = $featureConnection->aggregateMap();
        $this->assertCount(1, $aggregateMap);
        $this->assertAggregateBuilding($aggregateMap->current(), 'buTwEKKNLBBo6WAERYN1Gn');

        $eventMap = $featureConnection->eventMap();
        $this->assertCount(1, $eventMap);
        $this->assertEventBuildingAdded($eventMap->current());

        $commandMap = $featureConnection->commandMap();
        $this->assertCount(0, $commandMap);
    }

    /**
     * @test
     */
    public function it_returns_command_map_of_aggregate_node(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));

        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);
        $commandMap = $eventSourcingAnalyzer->commandMap();

        $this->assertCount(1, $commandMap);
        $command = $commandMap->current();

        $this->assertCommandAddBuilding($command, '9bJ5Y7yuBcfWyei7i2ZSDC');
    }

    /**
     * @test
     */
    public function it_returns_event_map_of_aggregate_node(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_user.json'));

        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);
        $eventMap = $eventSourcingAnalyzer->eventMap();

        $this->assertCount(2, $eventMap);
        $event = $eventMap->current();
        $this->assertEventUserCheckedIn($event);

        $eventMap->next();
        $event = $eventMap->current();
        $this->assertEventDoubleCheckInDetected($event);
    }

    /**
     * @test
     */
    public function it_returns_aggregate_connection_map_of_aggregate_node(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_user.json'));

        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);
        $aggregateMap = $eventSourcingAnalyzer->aggregateConnectionMap();

        $this->assertCount(1, $aggregateMap);
        $aggregate = $aggregateMap->current();
        $this->assertAggregateBuilding($aggregate->aggregate(), 'eiaS8gtsBemMReTNbeNRXj');

        $commandMap = $aggregate->commandMap();
        $this->assertCount(1, $commandMap);
        $command = $commandMap->current();
        $this->assertCommandCheckInUser($command);

        $eventMap = $aggregate->eventMap();
        $this->assertCount(2, $eventMap);
        $event = $eventMap->current();
        $this->assertEventUserCheckedIn($event);

        $eventMap->next();
        $event = $eventMap->current();
        $this->assertEventDoubleCheckInDetected($event);

        $commandsToEventsMap = $aggregate->commandsToEventsMap();

        $this->assertCount(1, $commandsToEventsMap);
        $this->assertTrue($commandsToEventsMap->offsetExists($command));

        $events = $commandsToEventsMap->offsetGet($command);
        $this->assertCount(2, $events);
    }

    /**
     * @test
     */
    public function it_returns_feature_connection_map_of_aggregate_node(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_user.json'));

        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);
        $featureConnectionMap = $eventSourcingAnalyzer->featureConnectionMap();

        $this->assertCount(1, $featureConnectionMap);
        $featureConnection = $featureConnectionMap->current();

        $commandMap = $featureConnection->commandMap();
        $this->assertCount(1, $commandMap);
        $command = $commandMap->current();
        $this->assertCommandCheckInUser($command);

        $eventMap = $featureConnection->eventMap();
        $this->assertCount(2, $eventMap);
        $event = $eventMap->current();
        $this->assertEventUserCheckedIn($event);

        $eventMap->next();
        $event = $eventMap->current();
        $this->assertEventDoubleCheckInDetected($event);
    }

    /**
     * @test
     */
    public function it_returns_feature_connection_map(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'feature_building.json'));

        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);

        $featureConnectionMap = $eventSourcingAnalyzer->featureConnectionMap();

        $this->assertCount(1, $featureConnectionMap);
        $featureConnection = $featureConnectionMap->current();

        $aggregateMap = $featureConnection->aggregateMap();
        $this->assertCount(1, $aggregateMap);

        $aggregate = $aggregateMap->current();
        $this->assertAggregateBuilding($aggregate, 'jKrpwfkdZnT5xMRKMYrgTF');
        $this->assertEquals($featureConnection->feature(), $featureConnectionMap->featureByAggregate($aggregate));

        $this->assertFeatureCommandMap($featureConnection->commandMap());
        $this->assertFeatureEventMap($featureConnection->eventMap());
        $this->assertCount(2, $featureConnection->documentMap());

        $aggregateConnectionMap = $eventSourcingAnalyzer->aggregateConnectionMap();
        $this->assertCount(3, $aggregateConnectionMap);

        $aggregateConnection = $aggregateConnectionMap->current();
        $this->assertCommandAddBuilding($aggregateConnection->commandMap()->current(), '9bJ5Y7yuBcfWyei7i2ZSDC');
        $this->assertEventBuildingAdded($aggregateConnection->eventMap()->current());

        $aggregateConnectionMap->next();
        $aggregateConnection = $aggregateConnectionMap->current();
        $this->assertCommandCheckInUser($aggregateConnection->commandMap()->current());

        $eventMap = $aggregateConnection->eventMap();
        $this->assertEventUserCheckedIn($eventMap->current());

        $eventMap->next();
        $this->assertEventDoubleCheckInDetected($eventMap->current());
    }

    /**
     * @test
     */
    public function it_analysis_nodes(): void
    {
        // order is important for assertions
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'add_building.json'));
        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);

        $this->assertCount(1, $eventSourcingAnalyzer->commandMap());
        $this->assertCount(1, $eventSourcingAnalyzer->aggregateMap());
        $this->assertCount(0, $eventSourcingAnalyzer->eventMap());
        $this->assertCount(1, $eventSourcingAnalyzer->aggregateConnectionMap());
        $this->assertCount(0, $eventSourcingAnalyzer->documentMap());
        $this->assertCount(0, $eventSourcingAnalyzer->policyMap());
        $this->assertCount(0, $eventSourcingAnalyzer->uiMap());
        $this->assertCount(0, $eventSourcingAnalyzer->externalSystemMap());
        $this->assertCount(0, $eventSourcingAnalyzer->hotSpotMap());
        $this->assertCount(1, $eventSourcingAnalyzer->featureMap());
        $this->assertCount(1, $eventSourcingAnalyzer->featureConnectionMap());
        $this->assertCount(0, $eventSourcingAnalyzer->boundedContextMap());
        $this->assertAnalysisAddBuilding($eventSourcingAnalyzer, 'buTwEKKNLBBo6WAERYN1Gn', false);

        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_user.json'));
        $eventSourcingAnalyzer->analyse($node);

        $this->assertCount(2, $eventSourcingAnalyzer->commandMap());
        $this->assertCount(1, $eventSourcingAnalyzer->aggregateMap());
        $this->assertCount(2, $eventSourcingAnalyzer->eventMap());
        $this->assertCount(2, $eventSourcingAnalyzer->aggregateConnectionMap());
        $this->assertCount(0, $eventSourcingAnalyzer->documentMap());
        $this->assertCount(0, $eventSourcingAnalyzer->policyMap());
        $this->assertCount(0, $eventSourcingAnalyzer->uiMap());
        $this->assertCount(0, $eventSourcingAnalyzer->externalSystemMap());
        $this->assertCount(0, $eventSourcingAnalyzer->hotSpotMap());
        $this->assertCount(1, $eventSourcingAnalyzer->featureMap());
        $this->assertCount(1, $eventSourcingAnalyzer->featureConnectionMap());
        $this->assertCount(0, $eventSourcingAnalyzer->boundedContextMap());
        $this->assertAnalysisAddBuilding($eventSourcingAnalyzer, 'eiaS8gtsBemMReTNbeNRXj', false);
        $this->assertAnalysisBuildingUser($eventSourcingAnalyzer);

        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_added.json'));
        $eventSourcingAnalyzer->analyse($node);
        $this->assertAnalysisAddBuilding($eventSourcingAnalyzer, 'buTwEKKNLBBo6WAERYN1Gn', true);
        $this->assertAnalysisBuildingUser($eventSourcingAnalyzer);
        $this->assertAnalysisBuilding($eventSourcingAnalyzer, '4gYkBjXufnkWMN5ybfBvPq');

        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $eventSourcingAnalyzer->analyse($node);

        $this->assertCount(2, $eventSourcingAnalyzer->commandMap());
        $this->assertCount(1, $eventSourcingAnalyzer->aggregateMap());
        $this->assertCount(3, $eventSourcingAnalyzer->eventMap());
        $this->assertCount(2, $eventSourcingAnalyzer->aggregateConnectionMap());
        $this->assertCount(1, $eventSourcingAnalyzer->documentMap());
        $this->assertCount(0, $eventSourcingAnalyzer->policyMap());
        $this->assertCount(0, $eventSourcingAnalyzer->uiMap());
        $this->assertCount(0, $eventSourcingAnalyzer->externalSystemMap());
        $this->assertCount(0, $eventSourcingAnalyzer->hotSpotMap());
        $this->assertCount(1, $eventSourcingAnalyzer->featureMap());
        $this->assertCount(1, $eventSourcingAnalyzer->featureConnectionMap());
        $this->assertCount(0, $eventSourcingAnalyzer->boundedContextMap());
        $this->assertAnalysisAddBuilding($eventSourcingAnalyzer, 'buTwEKKNLBBo6WAERYN1Gn', true);
        $this->assertAnalysisBuildingUser($eventSourcingAnalyzer);
        $this->assertAnalysisBuilding($eventSourcingAnalyzer, '4gYkBjXufnkWMN5ybfBvPq');

        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'feature_building.json'));
        $eventSourcingAnalyzer->analyse($node);

        $this->assertCount(3, $eventSourcingAnalyzer->commandMap());
        $this->assertCount(1, $eventSourcingAnalyzer->aggregateMap());
        $this->assertCount(4, $eventSourcingAnalyzer->eventMap());
        $this->assertCount(3, $eventSourcingAnalyzer->aggregateConnectionMap());
        $this->assertCount(2, $eventSourcingAnalyzer->documentMap());
        $this->assertCount(0, $eventSourcingAnalyzer->policyMap());
        $this->assertCount(0, $eventSourcingAnalyzer->uiMap());
        $this->assertCount(0, $eventSourcingAnalyzer->externalSystemMap());
        $this->assertCount(0, $eventSourcingAnalyzer->hotSpotMap());
        $this->assertCount(1, $eventSourcingAnalyzer->featureMap());
        $this->assertCount(1, $eventSourcingAnalyzer->featureConnectionMap());
        $this->assertCount(0, $eventSourcingAnalyzer->boundedContextMap());
        $this->assertAnalysisAddBuilding($eventSourcingAnalyzer, 'jKrpwfkdZnT5xMRKMYrgTF', true);
        $this->assertAnalysisBuildingUser($eventSourcingAnalyzer);
        $this->assertAnalysisBuilding($eventSourcingAnalyzer, 'f9iDWVAz8qT4j37oNzoU1p');
        $this->analyzeFeatureBuilding($eventSourcingAnalyzer);
    }

    private function assertAnalysisAddBuilding(
        EventSourcingAnalyzer $eventSourcingAnalyzer,
        string $aggregateId,
        bool $aggregateConnectionMapOfCommandAddBuildingWithEvent
    ): void {
        $commandMap = $eventSourcingAnalyzer->commandMap();
        $aggregateMap = $eventSourcingAnalyzer->aggregateMap();
        $aggregateConnectionMap = $eventSourcingAnalyzer->aggregateConnectionMap();

        // commands
        $command = $commandMap->current();
        $this->assertCommandAddBuilding($command, '9bJ5Y7yuBcfWyei7i2ZSDC');
        $commandAddBuildingObjectHash = \spl_object_hash($command);

        // aggregates
        $aggregates = $aggregateMap->current();
        $this->assertAggregateBuilding($aggregates, $aggregateId);

        // aggregate connections
        $aggregateConnection = $aggregateConnectionMap->current();
        $this->assertAggregateConnectionMapOfCommandAddBuilding($aggregateConnection, $aggregateConnectionMapOfCommandAddBuildingWithEvent);
        $aggregateConnectionCommandAddBuildingObjectHash = \spl_object_hash($aggregateConnection->commandMap()->current());

        $this->assertSame($commandAddBuildingObjectHash, $aggregateConnectionCommandAddBuildingObjectHash);
    }

    private function assertAnalysisBuildingUser(EventSourcingAnalyzer $eventSourcingAnalyzer): void
    {
        $commandMap = $eventSourcingAnalyzer->commandMap();
        $aggregateConnectionMap = $eventSourcingAnalyzer->aggregateConnectionMap();
        $eventMap = $eventSourcingAnalyzer->eventMap();

        // commands
        $commandMap->next();
        $command = $commandMap->current();
        $this->assertCommandCheckInUser($command);
        $commandCheckInUserObjectHash = \spl_object_hash($command);

        // events
        $eventMap->rewind();
        $event = $eventMap->current();
        $this->assertEventUserCheckedIn($event);

        $eventMap->next();
        $event = $eventMap->current();
        $this->assertEventDoubleCheckInDetected($event);

        // aggregate connections
        $aggregateConnectionMap->next();
        $aggregateConnection = $aggregateConnectionMap->current();
        $this->assertAggregateConnectionMapOfCommandCheckInUser($aggregateConnection);
        $aggregateConnectionCommandCheckInUserObjectHash = \spl_object_hash($aggregateConnection->commandMap()->current());
        $this->assertSame($commandCheckInUserObjectHash, $aggregateConnectionCommandCheckInUserObjectHash);
    }

    private function assertAnalysisBuilding(
        EventSourcingAnalyzer $eventSourcingAnalyzer,
        string $id
    ): void {
        $eventMap = $eventSourcingAnalyzer->eventMap();
        $documentMap = $eventSourcingAnalyzer->documentMap();

        // events
        $eventMap->next();
        $event = $eventMap->current();
        $this->assertEventBuildingAdded($event);

        // documents
        $documentMap->rewind();
        $document = $documentMap->current();
        $this->assertDocumentBuilding($document, $id);
    }

    private function analyzeFeatureBuilding(EventSourcingAnalyzer $eventSourcingAnalyzer): void
    {
        $commandMap = $eventSourcingAnalyzer->commandMap();
        $aggregateConnectionMap = $eventSourcingAnalyzer->aggregateConnectionMap();
        $eventMap = $eventSourcingAnalyzer->eventMap();
        $documentMap = $eventSourcingAnalyzer->documentMap();

        // commands
        $commandMap->next();
        $command = $commandMap->current();
        $this->assertCommandCheckOutUser($command);
        $commandCheckOutUserObjectHash = \spl_object_hash($command);

        // events
        $eventMap->next();
        $event = $eventMap->current();
        $this->assertEventUserCheckedOut($event);

        // aggregate connections
        $aggregateConnectionMap->next();
        $aggregateConnection = $aggregateConnectionMap->current();
        $this->assertAggregateConnectionMapOfCommandCheckOutUser($aggregateConnection);
        $aggregateConnectionCommandCheckOutUserObjectHash = \spl_object_hash($aggregateConnection->commandMap()->current());
        $this->assertSame($commandCheckOutUserObjectHash, $aggregateConnectionCommandCheckOutUserObjectHash);

        // documents
        $documentMap->next();
        $document = $documentMap->current();
        $this->assertDocumentUsersInBuilding($document, 't4mMTjg462VRvMW1L6nSGB');
    }

    private function assertFeatureCommandMap(VertexMap $commandMap): void
    {
        $this->assertCount(3, $commandMap);

        $command = $commandMap->current();
        $this->assertCommandAddBuilding($command, '9bJ5Y7yuBcfWyei7i2ZSDC');

        $commandMap->next();
        $command = $commandMap->current();
        $this->assertCommandCheckInUser($command);
    }

    private function assertFeatureEventMap(VertexMap $eventMap): void
    {
        $this->assertCount(4, $eventMap);
        $event = $eventMap->current();
        $this->assertEventBuildingAdded($event);

        $eventMap->next();
        $event = $eventMap->current();
        $this->assertEventUserCheckedIn($event);

        $eventMap->next();
        $event = $eventMap->current();
        $this->assertEventUserCheckedOut($event);

        $eventMap->next();
        $event = $eventMap->current();
        $this->assertEventDoubleCheckInDetected($event);
    }

    private function assertAggregateBuilding(AggregateType $aggregate, string $id): void
    {
        $this->assertSame($id, $aggregate->id());
        $this->assertSame('Building', $aggregate->name());
        $this->assertSame('Building ', $aggregate->label());
    }

    private function assertCommandAddBuilding(CommandType $command, string $id): void
    {
        $this->assertSame($id, $command->id());
        $this->assertSame('Add Building', $command->name());
    }

    private function assertEventBuildingAdded(EventType $event): void
    {
        $this->assertSame('tF2ZuZCXsdQMhRmRXydfuW', $event->id());
        $this->assertSame('Building Added', $event->name());
        $this->assertSame('Building Added', $event->label());
    }

    private function assertCommandCheckInUser(CommandType $command): void
    {
        $this->assertSame('aKvhibi95v18MKjNjb6tL3', $command->id());
        $this->assertSame('Check In User', $command->name());
        $this->assertSame('Check In User', \trim($command->label()));
    }

    private function assertCommandCheckOutUser(CommandType $command): void
    {
        $this->assertSame('dkNTGinM6VY1Qu8zyGURU1', $command->id());
        $this->assertSame('Check Out User', $command->name());
        $this->assertSame('Check Out User', \trim($command->label()));
    }

    private function assertEventUserCheckedIn(EventType $event): void
    {
        $this->assertSame('q3thtbbiWsgyRqGadCBLte', $event->id());
        $this->assertSame('User Checked In', $event->name());
        $this->assertSame('User Checked In', $event->label());
    }

    private function assertEventUserCheckedOut(EventType $event): void
    {
        $this->assertSame('5cVD57Gt2HtxPU6zonD5vx', $event->id());
        $this->assertSame('User Checked Out', $event->name());
        $this->assertSame('User Checked Out', $event->label());
    }

    private function assertEventDoubleCheckInDetected(EventType $event): void
    {
        $this->assertSame('8H79vCoLa3Y2RrpVy7ZMYE', $event->id());
        $this->assertSame('Double Check In Detected', $event->name());
        $this->assertSame('Double Check In Detected ', $event->label());
    }

    private function assertDocumentName(DocumentType $event): void
    {
        $this->assertSame('a4HLmzMb2g2MVQWXy4BKN1', $event->id());
        $this->assertSame('Name', $event->name());
        $this->assertSame('Name', $event->label());
    }

    private function assertDocumentBuilding(DocumentType $event, string $id): void
    {
        $this->assertSame($id, $event->id());
        $this->assertSame('Building', $event->name());
        $this->assertSame('Building ', $event->label());
    }

    private function assertDocumentUsersInBuilding(DocumentType $event, string $id): void
    {
        $this->assertSame($id, $event->id());
        $this->assertSame('Users In Building', $event->name());
        $this->assertSame('Users In Building ', $event->label());
    }
}
