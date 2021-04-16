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

        $this->assertCommandAddBuilding($command);
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

        $this->assertCommandAddBuilding($featureConnection->commandMap()->current());
        $this->assertAggregateBuilding($featureConnection->aggregateMap()->current(), 'buTwEKKNLBBo6WAERYN1Gn');
    }

    /**
     * @test
     */
    public function it_returns_aggregate_connection_map_of_command_node(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'add_building.json'));

        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);
        $aggregateMap = $eventSourcingAnalyzer->aggregateConnectionMap();

        $this->assertCount(1, $aggregateMap);
        $aggregate = $aggregateMap->current();
        $this->assertAggregateBuilding($aggregate->aggregate(), 'buTwEKKNLBBo6WAERYN1Gn');

        $commandMap = $aggregate->commandMap();
        $this->assertCount(1, $commandMap);
        $this->assertCommandAddBuilding($commandMap->current());

        $eventMap = $aggregate->eventMap();
        $this->assertCount(0, $eventMap);
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

        $this->assertCommandAddBuilding($command);
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
        $this->assertCommandAddBuilding($aggregateConnection->commandMap()->current());
        $this->assertEventBuildingAdded($aggregateConnection->eventMap()->current());

        $aggregateConnectionMap->next();
        $aggregateConnection = $aggregateConnectionMap->current();
        $this->assertCommandCheckInUser($aggregateConnection->commandMap()->current());

        $eventMap = $aggregateConnection->eventMap();
        $this->assertEventUserCheckedIn($eventMap->current());

        $eventMap->next();
        $this->assertEventDoubleCheckInDetected($eventMap->current());
    }

    private function assertFeatureCommandMap(VertexMap $commandMap): void
    {
        $this->assertCount(3, $commandMap);

        $command = $commandMap->current();
        $this->assertCommandAddBuilding($command);

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

    private function assertCommandAddBuilding(CommandType $command): void
    {
        $this->assertSame('9bJ5Y7yuBcfWyei7i2ZSDC', $command->id());
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
}
