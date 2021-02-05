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
    public function it_returns_aggregate_map_of_command_node(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'add_building.json'));

        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);
        $aggregateMap = $eventSourcingAnalyzer->aggregateMap();

        $this->assertCount(1, $aggregateMap);
        $aggregate = $aggregateMap->current();
        $this->assertAggregateBuilding($aggregate->aggregate(), 'o67B4pXhbDvuF2BnBsBy27');

        $commandMap = $aggregate->commandMap();
        $this->assertCount(1, $commandMap);
        $this->assertCommandAddBuilding($commandMap->current());
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
    public function it_returns_aggregate_map_of_aggregate_node(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_user.json'));

        $eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $this->filter);
        $aggregateMap = $eventSourcingAnalyzer->aggregateMap();

        $this->assertCount(1, $aggregateMap);
        $aggregate = $aggregateMap->current();
        $this->assertAggregateBuilding($aggregate->aggregate(), 'juqq2zqsYU44UgrAad65Ws');

        $commandMap = $aggregate->commandMap();
        $this->assertCount(1, $commandMap);
        $this->assertCommandCheckInUser($commandMap->current());

        $eventMap = $aggregate->eventMap();
        $this->assertCount(2, $eventMap);
        $event = $eventMap->current();
        $this->assertEventUserCheckedIn($event);

        $eventMap->next();
        $event = $eventMap->current();
        $this->assertEventDoubleCheckInDetected($event);
    }

    private function assertAggregateBuilding(AggregateType $aggregate, string $id): void
    {
        $this->assertSame($id, $aggregate->id());
        $this->assertSame('Building', $aggregate->name());
        $this->assertSame('Building', $aggregate->label());
    }

    private function assertCommandAddBuilding(CommandType $command): void
    {
        $this->assertSame('i5hHo93xQP8cvhdTh25DHq', $command->id());
        $this->assertSame('Add Building', $command->name());
        $this->assertSame('Add Building', $command->label());
    }

    private function assertEventBuildingAdded(EventType $event): void
    {
        $this->assertSame('ctuHbHKF1pwqQfa3VZ1nfz', $event->id());
        $this->assertSame('BuildingAdded', $event->name());
        $this->assertSame('BuildingAdded', $event->label());
    }

    private function assertCommandCheckInUser(CommandType $command): void
    {
        $this->assertSame('i26gh6vK7QCsNwpvW4CuYX', $command->id());
        $this->assertSame('CheckInUser', $command->name());
        $this->assertSame('CheckInUser', $command->label());
    }

    private function assertEventUserCheckedIn(EventType $event): void
    {
        $this->assertSame('ondJmx9Xo19YLYJk1DqFeL', $event->id());
        $this->assertSame('UserCheckedIn', $event->name());
        $this->assertSame('UserCheckedIn', $event->label());
    }

    private function assertEventDoubleCheckInDetected(EventType $event): void
    {
        $this->assertSame('uNMg3RfYfAsdD7pvzt1zY9', $event->id());
        $this->assertSame('DoubleCheckInDetected', $event->name());
        $this->assertSame('DoubleCheckInDetected', $event->label());
    }
}
