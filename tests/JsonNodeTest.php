<?php

/**
 * @see       https://github.com/event-engine/php-inspectio-graph-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-inspectio-graph-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-inspectio-graph-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\InspectioGraphCody;

use EventEngine\InspectioGraphCody\JsonNode;
use EventEngine\InspectioGraphCody\Node;
use PHPUnit\Framework\TestCase;

final class JsonNodeTest extends TestCase
{
    private const FILES_DIR = __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR;

    /**
     * @test
     */
    public function it_can_be_created_from_json(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));

        $this->assertSame('buTwEKKNLBBo6WAERYN1Gn', $node->id());
        $this->assertSame('Building', \trim($node->name()));
        $this->assertSame('aggregate', $node->type());
        $this->assertFalse($node->isLayer());
        $this->assertFalse($node->isDefaultLayer());
    }

    /**
     * @test
     */
    public function it_returns_parent_node(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'add_building.json'));

        $parent = $node->parent();
        $this->assertFeatureNode($parent);

        $parent = $parent->parent();
        $this->assertBoundedContextNode($parent);

        $parent = $parent->parent();
        $this->assertBoardLayerNode($parent);
    }

    /**
     * @test
     */
    public function it_returns_sources(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));

        foreach ($node->sources() as $source) {
            $this->assertSame('9bJ5Y7yuBcfWyei7i2ZSDC', $source->id());
            $this->assertSame('Add Building', \trim($source->name()));
            $this->assertSame('command', $source->type());
            $this->assertFalse($source->isLayer());
            $this->assertFalse($source->isDefaultLayer());

            $parent = $node->parent();
            $this->assertFeatureNode($parent);

            $parent = $parent->parent();
            $this->assertBoundedContextNode($parent);

            $parent = $parent->parent();
            $this->assertBoardLayerNode($parent);
        }
    }

    /**
     * @test
     */
    public function it_returns_targets(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));

        foreach ($node->targets() as $target) {
            $this->assertSame('tF2ZuZCXsdQMhRmRXydfuW', $target->id());
            $this->assertSame('Building Added', $target->name());
            $this->assertSame('event', $target->type());
            $this->assertFalse($target->isLayer());
            $this->assertFalse($target->isDefaultLayer());

            $parent = $node->parent();
            $this->assertFeatureNode($parent);

            $parent = $parent->parent();
            $this->assertBoundedContextNode($parent);

            $parent = $parent->parent();
            $this->assertBoardLayerNode($parent);
        }
    }

    private function assertFeatureNode(Node $node): void
    {
        $this->assertInstanceOf(Node::class, $node);
        $this->assertSame('stW5qRRPsQbowcqux7M2QX', $node->id());
        $this->assertSame('Building', $node->name());
        $this->assertSame('feature', $node->type());
        $this->assertFalse($node->isLayer());
        $this->assertFalse($node->isDefaultLayer());
    }

    private function assertBoundedContextNode(Node $node): void
    {
        $this->assertInstanceOf(Node::class, $node);
        $this->assertSame('2FqsFfW5xGzooq2fdFTfaa', $node->id());
        $this->assertSame('Hotel', $node->name());
        $this->assertSame('boundedContext', $node->type());
        $this->assertFalse($node->isLayer());
        $this->assertFalse($node->isDefaultLayer());
    }

    private function assertBoardLayerNode(Node $node): void
    {
        $this->assertInstanceOf(Node::class, $node);
        $this->assertSame('7fe80d19-d317-4e9d-8296-96c598786d78', $node->id());
        $this->assertSame('Board', $node->name());
        $this->assertSame('layer', $node->type());
        $this->assertTrue($node->isLayer());
        $this->assertTrue($node->isDefaultLayer());
    }
}
