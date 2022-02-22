# Event Engine - PHP InspectIO Graph Cody

Implementation of the [*InspectIO Graph* specification](https://github.com/event-engine/php-inspectio-graph "InspectIO Graph specification") 
for [Cody Bot](https://github.com/event-engine/inspectio/wiki/PHP-Cody-Tutorial "PHP Cody Tutorial") to generate PHP code
based on the [InspectIO Event Map](https://github.com/event-engine/inspectio "InspectIO Event Map").

## Installation

```bash
$ composer require event-engine/php-inspectio-graph-cody
```

## Usage

> See unit tests (`EventSourcingAnalyzerTest`) in `tests` folder for comprehensive examples and how a Cody JSON looks like.

The following example gives a quick overview how to retrieve information from the `EventSourcingAnalyzer`.

```php
<?php

declare(strict_types=1);

use EventEngine\InspectioGraph\VertexConnectionMap;
use EventEngine\InspectioGraph\VertexType;
use EventEngine\InspectioGraphCody\EventSourcingAnalyzer;
use EventEngine\InspectioGraphCody\EventSourcingGraph;
use EventEngine\InspectioGraphCody\JsonNode;

// assume $filter is a callable to filter sticky / node names
$filter = static function (string $value) {
    return \trim($value);
};

// assume $json is a JSON string in Cody format
$node = JsonNode::fromJson($json);

$analyzer = new EventSourcingAnalyzer(new EventSourcingGraph($filter));
$connection = $analyzer->analyse($node); // analyze the Cody JSON node
// call $analyzer->analyse($anotherNode) again with other nodes to build up the graph

$identity = $connection->identity();

if ($identity->type() === VertexType::TYPE_AGGREGATE) {
    // get connected commands of connection, in this case the aggregate
    $commands = $connection->from()->filterByType(VertexType::TYPE_COMMAND);

    // get connected events of connection, in this case the aggregate
    $events = $connection->to()->filterByType(VertexType::TYPE_EVENT);

    // get parent of identity, could be a feature or bounded context
    $parent = $connection->parent();

    if ($parent->type() === VertexType::TYPE_FEATURE) {
        // get parent connection to get connected vertices
        $parentConnection = $analyzer->connection($parent->id());

        // cycle through all children (vertices e.g. commands, events, etc) of this parent
        foreach ($parentConnection->children() as $child) {
            if ($child->type() === VertexType::TYPE_COMMAND) {
                // ...
            }
        }
    }
}

// search in graph for first document in forwarding mode (a "to" connection)
$documentConnection = $analyzer->graph()->findInGraph(
    $identity->id(),
    static function (VertexType $vertex): bool {
        return $vertex->type() === VertexType::TYPE_DOCUMENT;
    },
    VertexConnectionMap::WALK_FORWARD
);
```
