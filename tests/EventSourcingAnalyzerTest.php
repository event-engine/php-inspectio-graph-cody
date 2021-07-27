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
use EventEngine\InspectioGraph\DocumentType;
use EventEngine\InspectioGraph\EventType;
use EventEngine\InspectioGraph\FeatureType;
use EventEngine\InspectioGraph\VertexType;
use EventEngine\InspectioGraphCody\EventSourcingAnalyzer;
use EventEngine\InspectioGraphCody\EventSourcingGraph;
use EventEngine\InspectioGraphCody\JsonNode;
use PHPUnit\Framework\TestCase;

final class EventSourcingAnalyzerTest extends TestCase
{
    private const FILES_DIR = __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR;
    private const ID_FEATURE = 'stW5qRRPsQbowcqux7M2QX';

    private const ID_ADD_BUILDING = '9bJ5Y7yuBcfWyei7i2ZSDC';
    private const ID_ADD_BUILDING_AGGREGATE = 'buTwEKKNLBBo6WAERYN1Gn';
    private const ID_BUILDING_ADDED = 'tF2ZuZCXsdQMhRmRXydfuW';

    private const ID_CHECK_IN_USER = 'aKvhibi95v18MKjNjb6tL3';
    private const ID_CHECK_IN_USER_AGGREGATE = 'eiaS8gtsBemMReTNbeNRXj';
    private const ID_USER_CHECKED_IN = 'q3thtbbiWsgyRqGadCBLte';
    private const ID_DOUBLE_CHECKED_IN_DETECTED = '8H79vCoLa3Y2RrpVy7ZMYE';

    private const ID_CHECK_OUT_USER = 'aKvhibi95v18MKjNjb6tL3';
    private const ID_CHECK_OUT_USER_AGGREGATE = 'eiaS8gtsBemMReTNbeNRXj';
    private const ID_USER_CHECKED_OUT = 'q3thtbbiWsgyRqGadCBLte';

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
    public function it_removes_nodes(): void
    {
        $analyzer = new EventSourcingAnalyzer(new EventSourcingGraph($this->filter));

        // order is important for assertions
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'add_building.json'));
        $identityConnection = $analyzer->analyse($node);

        $this->assertCount(1, $analyzer->commandMap());
        $this->assertCount(1, $analyzer->aggregateMap());
        $this->assertCount(0, $analyzer->eventMap());
        $this->assertCount(0, $analyzer->documentMap());
        $this->assertCount(0, $analyzer->policyMap());
        $this->assertCount(0, $analyzer->uiMap());
        $this->assertCount(0, $analyzer->externalSystemMap());
        $this->assertCount(0, $analyzer->hotSpotMap());
        $this->assertCount(1, $analyzer->featureMap());
        $this->assertCount(1, $analyzer->boundedContextMap());
        $this->assertCommandAddBuilding($identityConnection->identity(), self::ID_ADD_BUILDING);
        $this->assertAnalysisAddBuilding($analyzer, false);

        $analyzer->remove($node);

        $this->assertCount(0, $analyzer->commandMap());
        $this->assertCount(1, $analyzer->aggregateMap());
        $this->assertCount(0, $analyzer->eventMap());
        $this->assertCount(0, $analyzer->documentMap());
        $this->assertCount(0, $analyzer->policyMap());
        $this->assertCount(0, $analyzer->uiMap());
        $this->assertCount(0, $analyzer->externalSystemMap());
        $this->assertCount(0, $analyzer->hotSpotMap());
        $this->assertCount(1, $analyzer->featureMap());
        $this->assertCount(1, $analyzer->boundedContextMap());
    }

    /**
     * @test
     */
    public function it_analysis_nodes(): void
    {
        $analyzer = new EventSourcingAnalyzer(new EventSourcingGraph($this->filter));

        // order is important for assertions
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'add_building.json'));
        $identityConnection = $analyzer->analyse($node);

        $this->assertCount(1, $analyzer->commandMap());
        $this->assertCount(1, $analyzer->aggregateMap());
        $this->assertCount(0, $analyzer->eventMap());
        $this->assertCount(0, $analyzer->documentMap());
        $this->assertCount(0, $analyzer->policyMap());
        $this->assertCount(0, $analyzer->uiMap());
        $this->assertCount(0, $analyzer->externalSystemMap());
        $this->assertCount(0, $analyzer->hotSpotMap());
        $this->assertCount(1, $analyzer->featureMap());
        $this->assertCount(1, $analyzer->boundedContextMap());
        $this->assertCommandAddBuilding($identityConnection->identity(), self::ID_ADD_BUILDING);
        $this->assertAnalysisAddBuilding($analyzer, false);

        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_user.json'));
        $identityConnection = $analyzer->analyse($node);

        $this->assertCount(2, $analyzer->commandMap());
        $this->assertCount(2, $analyzer->aggregateMap());
        $this->assertCount(2, $analyzer->eventMap());
        $this->assertCount(0, $analyzer->documentMap());
        $this->assertCount(0, $analyzer->policyMap());
        $this->assertCount(0, $analyzer->uiMap());
        $this->assertCount(0, $analyzer->externalSystemMap());
        $this->assertCount(0, $analyzer->hotSpotMap());
        $this->assertCount(1, $analyzer->featureMap());
        $this->assertCount(1, $analyzer->boundedContextMap());
        $this->assertAggregateBuilding($identityConnection->identity(), self::ID_CHECK_IN_USER_AGGREGATE);
        $this->assertAnalysisAddBuilding($analyzer, false);
        $this->assertAnalysisBuildingUser($analyzer);

        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_added.json'));
        $identityConnection = $analyzer->analyse($node);

        $this->assertCount(2, $analyzer->commandMap());
        $this->assertCount(2, $analyzer->aggregateMap());
        $this->assertCount(3, $analyzer->eventMap());
        $this->assertCount(1, $analyzer->documentMap());
        $this->assertCount(0, $analyzer->policyMap());
        $this->assertCount(0, $analyzer->uiMap());
        $this->assertCount(0, $analyzer->externalSystemMap());
        $this->assertCount(0, $analyzer->hotSpotMap());
        $this->assertCount(1, $analyzer->featureMap());
        $this->assertCount(1, $analyzer->boundedContextMap());
        $this->assertEventBuildingAdded($identityConnection->identity());
        $this->assertAnalysisAddBuilding($analyzer, true);
        $this->assertAnalysisBuildingUser($analyzer);
        $this->assertAnalysisBuildingAdded($analyzer);

        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $identityConnection = $analyzer->analyse($node);

        $this->assertCount(2, $analyzer->commandMap());
        $this->assertCount(2, $analyzer->aggregateMap());
        $this->assertCount(3, $analyzer->eventMap());
        $this->assertCount(1, $analyzer->documentMap());
        $this->assertCount(0, $analyzer->policyMap());
        $this->assertCount(0, $analyzer->uiMap());
        $this->assertCount(0, $analyzer->externalSystemMap());
        $this->assertCount(0, $analyzer->hotSpotMap());
        $this->assertCount(1, $analyzer->featureMap());
        $this->assertCount(1, $analyzer->boundedContextMap());
        $this->assertAggregateBuilding($identityConnection->identity(), self::ID_ADD_BUILDING_AGGREGATE);
        $this->assertAnalysisAddBuilding($analyzer, true);
        $this->assertAnalysisBuildingUser($analyzer);
        $this->assertAnalysisBuildingAdded($analyzer);
        $this->assertAnalysisBuilding($analyzer);

        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'feature_building.json'));
        $identityConnection = $analyzer->analyse($node);

        $this->assertCount(3, $analyzer->commandMap());
        $this->assertCount(3, $analyzer->aggregateMap());
        $this->assertCount(4, $analyzer->eventMap());
        $this->assertCount(3, $analyzer->documentMap());
        $this->assertCount(0, $analyzer->policyMap());
        $this->assertCount(0, $analyzer->uiMap());
        $this->assertCount(0, $analyzer->externalSystemMap());
        $this->assertCount(0, $analyzer->hotSpotMap());
        $this->assertCount(1, $analyzer->featureMap());
        $this->assertCount(1, $analyzer->boundedContextMap());
        $this->assertFeature($identityConnection->identity());
        $this->assertAnalysisAddBuilding($analyzer, true);
        $this->assertAnalysisBuildingUser($analyzer);
        $this->assertAnalysisBuildingAdded($analyzer);
        $this->assertAnalysisBuilding($analyzer);
        $this->assertAnalyzeFeatureBuilding($analyzer);
    }

    private function assertAnalysisAddBuilding(
        EventSourcingAnalyzer $analyzer,
        bool $updated
    ): void {
        $commandMap = $analyzer->commandMap();
        $aggregateMap = $analyzer->aggregateMap();
        $featureMap = $analyzer->featureMap();

        $command = $commandMap->current();
        $aggregate = $aggregateMap->current();
        $feature = $featureMap->current();

        $this->assertCount(0, $command->from());
        $this->assertCount(1, $command->to());
        $this->assertCount(1, $aggregate->from());
        $this->assertCount($updated ? 1 : 0, $aggregate->to());

        // commands
        $this->assertCommandAddBuilding($command->identity(), self::ID_ADD_BUILDING);

        // aggregates
        $this->assertAggregateBuilding($aggregate->identity(), self::ID_ADD_BUILDING_AGGREGATE);

        // aggregate connections
        $this->assertIdenticalVertex($aggregate->identity(), $command->to()->current());
        $this->assertIdenticalVertex($command->identity(), $aggregate->from()->current());

        // feature connections
        $this->assertIdenticalVertex($aggregate->parent(), $feature->identity());
        $this->assertIdenticalVertex($command->parent(), $feature->identity());
        $this->assertIdenticalVertex($feature->children()->filterByType(VertexType::TYPE_COMMAND)->current(), $command->identity());
        $this->assertIdenticalVertex($feature->children()->filterByType(VertexType::TYPE_AGGREGATE)->current(), $aggregate->identity());
    }

    private function assertAnalysisBuildingUser(EventSourcingAnalyzer $analyzer): void
    {
        $commandMap = $analyzer->commandMap();
        $aggregateMap = $analyzer->aggregateMap();
        $eventMap = $analyzer->eventMap();
        $featureMap = $analyzer->featureMap();

        $commandMap->next();
        $aggregateMap->next();

        // commands
        $command = $commandMap->current();
        $command->from()->rewind();
        $command->to()->rewind();

        $this->assertCommandCheckInUser($command->identity());

        // aggregates
        $aggregate = $aggregateMap->current();
        $aggregate->from()->rewind();
        $aggregate->to()->rewind();

        $this->assertAggregateBuilding($aggregate->identity(), self::ID_CHECK_IN_USER_AGGREGATE);

        // events
        $eventUserCheckedIn = $eventMap->current();
        $eventUserCheckedIn->from()->rewind();
        $eventUserCheckedIn->to()->rewind();

        $this->assertEventUserCheckedIn($eventUserCheckedIn->identity());

        $eventMap->next();
        $eventDoubleCheckInDetected = $eventMap->current();
        $eventDoubleCheckInDetected->from()->rewind();
        $eventDoubleCheckInDetected->to()->rewind();

        $this->assertEventDoubleCheckInDetected($eventDoubleCheckInDetected->identity());

        // aggregate connections
        $this->assertIdenticalVertex($aggregate->identity(), $command->to()->current());
        $this->assertIdenticalVertex($command->identity(), $aggregate->from()->current());

        $this->assertIdenticalVertex($aggregate->identity(), $eventUserCheckedIn->from()->current());
        $this->assertIdenticalVertex($aggregate->identity(), $eventDoubleCheckInDetected->from()->current());

        $this->assertIdenticalVertex($eventUserCheckedIn->identity(), $aggregate->to()->current());

        $aggregate->to()->next();
        $this->assertIdenticalVertex($eventDoubleCheckInDetected->identity(), $aggregate->to()->current());

        // feature connections
        $feature = $featureMap->current();
        $childrenCommand = $feature->children()->filterByType(VertexType::TYPE_COMMAND);
        $childrenCommand->next();

        $childrenAggregate = $feature->children()->filterByType(VertexType::TYPE_AGGREGATE);
        $childrenAggregate->next();

        $this->assertIdenticalVertex($aggregate->parent(), $feature->identity());
        $this->assertIdenticalVertex($command->parent(), $feature->identity());
        $this->assertIdenticalVertex($childrenCommand->current(), $command->identity());
        $this->assertIdenticalVertex($childrenAggregate->current(), $aggregate->identity());
    }

    private function assertAnalysisBuilding(EventSourcingAnalyzer $analyzer): void
    {
        $command = $analyzer->connection(self::ID_ADD_BUILDING);
        $aggregate = $analyzer->connection(self::ID_ADD_BUILDING_AGGREGATE);
        $event = $analyzer->connection(self::ID_BUILDING_ADDED);
        $feature = $analyzer->connection(self::ID_FEATURE);

        $this->assertCount(0, $command->from());
        $this->assertCount(1, $command->to());
        $this->assertCount(1, $aggregate->from());
        $this->assertCount(1, $aggregate->to());
        $this->assertCount(1, $event->from());
        $this->assertCount(1, $event->to());

        // event
        $this->assertDocumentBuilding($event->to()->current(), '4gYkBjXufnkWMN5ybfBvPq');

        // aggregate connections
        $this->assertIdenticalVertex($aggregate->identity(), $command->to()->current());
        $this->assertIdenticalVertex($aggregate->identity(), $event->from()->current());
        $this->assertIdenticalVertex($command->identity(), $aggregate->from()->current());
        $this->assertIdenticalVertex($event->identity(), $aggregate->to()->current());

        // feature connections
        $this->assertIdenticalVertex($aggregate->parent(), $feature->identity());
        $this->assertIdenticalVertex($command->parent(), $feature->identity());
        $this->assertIdenticalVertex($event->parent(), $feature->identity());
    }

    private function assertAnalysisBuildingAdded(EventSourcingAnalyzer $analyzer): void
    {
        $commandMap = $analyzer->commandMap();
        $aggregateMap = $analyzer->aggregateMap();
        $eventMap = $analyzer->eventMap();
        $featureMap = $analyzer->featureMap();

        $commandMap->next();
        $aggregateMap->next();

        // commands
        $command = $commandMap->current();
        $command->from()->rewind();
        $command->to()->rewind();

        $this->assertCommandCheckInUser($command->identity());

        // aggregates
        $aggregate = $aggregateMap->current();
        $aggregate->from()->rewind();
        $aggregate->to()->rewind();

        $this->assertAggregateBuilding($aggregate->identity(), self::ID_CHECK_IN_USER_AGGREGATE);

        // events
        $eventUserCheckedIn = $eventMap->current();
        $eventUserCheckedIn->from()->rewind();
        $eventUserCheckedIn->to()->rewind();

        $this->assertEventUserCheckedIn($eventUserCheckedIn->identity());

        $eventMap->next();
        $eventDoubleCheckInDetected = $eventMap->current();
        $eventDoubleCheckInDetected->from()->rewind();
        $eventDoubleCheckInDetected->to()->rewind();

        $this->assertEventDoubleCheckInDetected($eventDoubleCheckInDetected->identity());

        // aggregate connections
        $this->assertIdenticalVertex($aggregate->identity(), $command->to()->current());
        $this->assertIdenticalVertex($command->identity(), $aggregate->from()->current());

        $this->assertIdenticalVertex($aggregate->identity(), $eventUserCheckedIn->from()->current());
        $this->assertIdenticalVertex($aggregate->identity(), $eventDoubleCheckInDetected->from()->current());

        $this->assertIdenticalVertex($eventUserCheckedIn->identity(), $aggregate->to()->current());

        $aggregate->to()->next();
        $this->assertIdenticalVertex($eventDoubleCheckInDetected->identity(), $aggregate->to()->current());

        // feature connections
        $feature = $featureMap->current();
        $childrenCommand = $feature->children()->filterByType(VertexType::TYPE_COMMAND);
        $childrenCommand->next();

        $childrenAggregate = $feature->children()->filterByType(VertexType::TYPE_AGGREGATE);
        $childrenAggregate->next();

        $this->assertIdenticalVertex($aggregate->parent(), $feature->identity());
        $this->assertIdenticalVertex($command->parent(), $feature->identity());
        $this->assertIdenticalVertex($childrenCommand->current(), $command->identity());
        $this->assertIdenticalVertex($childrenAggregate->current(), $aggregate->identity());
    }

    private function assertAnalyzeFeatureBuilding(EventSourcingAnalyzer $analyzer): void
    {
        $commandMap = $analyzer->commandMap();
        $aggregateMap = $analyzer->aggregateMap();
        $eventMap = $analyzer->eventMap();
        $documentMap = $analyzer->documentMap();
        $featureMap = $analyzer->featureMap();

        $feature = $featureMap->current();

        foreach ($feature->children()->filterByType(VertexType::TYPE_COMMAND) as $child) {
            $command = $commandMap->current();
            $this->assertIdenticalVertex($child, $command->identity());
            $this->assertSame($feature->identity(), $command->parent());
            $commandMap->next();
        }
        foreach ($feature->children()->filterByType(VertexType::TYPE_AGGREGATE) as $child) {
            $aggregate = $aggregateMap->current();
            $this->assertIdenticalVertex($child, $aggregate->identity());
            $this->assertSame($feature->identity(), $aggregate->parent());
            $aggregateMap->next();
        }
        foreach ($feature->children()->filterByType(VertexType::TYPE_EVENT) as $child) {
            $event = $eventMap->current();
            $this->assertIdenticalVertex($child, $event->identity());
            $this->assertSame($feature->identity(), $event->parent());
            $eventMap->next();
        }
        foreach ($feature->children()->filterByType(VertexType::TYPE_DOCUMENT) as $child) {
            $document = $documentMap->current();
            $this->assertIdenticalVertex($child, $document->identity());
            $this->assertSame($feature->identity(), $document->parent());
            $documentMap->next();
        }
    }

    private function assertIdenticalVertex(VertexType $a, VertexType $b): void
    {
        $this->assertSame($a->type(), $b->type(), 'The type does not match');
        $this->assertSame($a->name(), $b->name(), 'The name does not match');
        $this->assertSame($a->id(), $b->id(), 'The ids does not match');
        $this->assertSame(\spl_object_hash($a), \spl_object_hash($b), 'The objects are not identical');
    }

    private function assertFeature(FeatureType $feature): void
    {
        $this->assertSame(self::ID_FEATURE, $feature->id());
        $this->assertSame('Building', $feature->name());
        $this->assertSame('Building', $feature->label());
        $this->assertSame('feature', $feature->type());
    }

    private function assertAggregateBuilding(AggregateType $aggregate, string $id): void
    {
        $this->assertSame($id, $aggregate->id());
        $this->assertSame('Building', $aggregate->name());
        $this->assertSame('Building ', $aggregate->label());
        $this->assertSame('aggregate', $aggregate->type());
    }

    private function assertCommandAddBuilding(CommandType $command, string $id): void
    {
        $this->assertSame($id, $command->id());
        $this->assertSame('Add Building', $command->name());
        $this->assertSame('command', $command->type());
    }

    private function assertEventBuildingAdded(EventType $event): void
    {
        $this->assertSame('tF2ZuZCXsdQMhRmRXydfuW', $event->id());
        $this->assertSame('Building Added', $event->name());
        $this->assertSame('Building Added', $event->label());
        $this->assertSame('event', $event->type());
    }

    private function assertCommandCheckInUser(CommandType $command): void
    {
        $this->assertSame('aKvhibi95v18MKjNjb6tL3', $command->id());
        $this->assertSame('Check In User', $command->name());
        $this->assertSame('Check In User', \trim($command->label()));
        $this->assertSame('command', $command->type());
    }

    private function assertCommandCheckOutUser(CommandType $command): void
    {
        $this->assertSame('dkNTGinM6VY1Qu8zyGURU1', $command->id());
        $this->assertSame('Check Out User', $command->name());
        $this->assertSame('Check Out User', \trim($command->label()));
        $this->assertSame('commmand', $command->type());
    }

    private function assertEventUserCheckedIn(EventType $event): void
    {
        $this->assertSame('q3thtbbiWsgyRqGadCBLte', $event->id());
        $this->assertSame('User Checked In', $event->name());
        $this->assertSame('User Checked In', $event->label());
        $this->assertSame('event', $event->type());
    }

    private function assertEventUserCheckedOut(EventType $event): void
    {
        $this->assertSame('5cVD57Gt2HtxPU6zonD5vx', $event->id());
        $this->assertSame('User Checked Out', $event->name());
        $this->assertSame('User Checked Out', $event->label());
        $this->assertSame('event', $event->type());
    }

    private function assertEventDoubleCheckInDetected(EventType $event): void
    {
        $this->assertSame('8H79vCoLa3Y2RrpVy7ZMYE', $event->id());
        $this->assertSame('Double Check In Detected', $event->name());
        $this->assertSame('Double Check In Detected ', $event->label());
        $this->assertSame('event', $event->type());
    }

    private function assertDocumentName(DocumentType $document): void
    {
        $this->assertSame('a4HLmzMb2g2MVQWXy4BKN1', $document->id());
        $this->assertSame('Name', $document->name());
        $this->assertSame('Name', $document->label());
        $this->assertSame('document', $document->type());
    }

    private function assertDocumentBuilding(DocumentType $document, string $id): void
    {
        $this->assertSame($id, $document->id());
        $this->assertSame('Building', $document->name());
        $this->assertSame('Building ', $document->label());
        $this->assertSame('document', $document->type());
    }

    private function assertDocumentUsersInBuilding(DocumentType $document, string $id): void
    {
        $this->assertSame($id, $document->id());
        $this->assertSame('Users In Building', $document->name());
        $this->assertSame('Users In Building ', $document->label());
        $this->assertSame('document', $document->type());
    }
}
