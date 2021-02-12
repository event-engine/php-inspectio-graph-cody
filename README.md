# Event Engine - PHP InspectIO Graph Cody

Implementation of the [*InspectIO Graph* specification](https://github.com/event-engine/php-inspectio-graph "InspectIO Graph specification") 
for [Cody Bot](https://github.com/event-engine/inspectio/wiki/PHP-Cody-Tutorial "PHP Cody Tutorial") to generate PHP code
based on the [InspectIO Event Map](https://github.com/event-engine/inspectio "InspectIO Event Map").

## Installation

```bash
$ composer require event-engine/php-inspectio-graph-cody
```

## Usage

> See unit tests in `tests` folder for comprehensive examples and how a Cody JSON looks like.

The following example gives a quick overview how to retrieve information from the `EventSourcingAnalyzer`.

```php
<?php

use EventEngine\InspectioGraphCody\EventSourcingAnalyzer;
use EventEngine\InspectioGraphCody\JsonNode;

// assume $json is a JSON string in Cody format
// assume $filter is a callable to filter names / labels

// create a node instance from json
$node = JsonNode::fromJson($json);

// analyze the given Cody node 
$eventSourcingAnalyzer = new EventSourcingAnalyzer($node, $filter);

// get a list of all commands
$commands = $eventSourcingAnalyzer->commandMap();

// get a list of all domain events
$events = $eventSourcingAnalyzer->eventMap();

// get a list of all aggregates
$aggregates = $eventSourcingAnalyzer->aggregateMap();

foreach ($aggregates as $aggregate) {
    // returns the aggregate
    $aggregate->aggregate();

    // returns the corresponding commands for this aggregate
    $aggregate->commandMap();

    // returns the corresponding domain events for this aggregate
    $aggregate->eventMap();

    // returns commands with corresponding domain event(s) (the connection between command -> domain event(s))
    $aggregate->commandsToEventsMap();
}
```
