<?php

/**
 * @see       https://github.com/event-engine/php-inspectio-graph-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-inspectio-graph-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-inspectio-graph-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\InspectioGraphCody;

use EventEngine\InspectioGraph\Metadata\Metadata;
use EventEngine\InspectioGraph\VertexType;
use EventEngine\InspectioGraphCody\Exception\RuntimeException;

abstract class Vertex implements VertexType
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $name;

    /**
     * Arbitrary user metadata e. g. JSON schema
     *
     * @var string
     */
    protected $metadata;

    /**
     * @var mixed
     */
    protected $metadataInstance;

    public static function fromCodyNode(
        Node $vertex,
        callable $filterName,
        ?callable $metadataFactory = null
    ): VertexType {
        $label = $vertex->name();
        $type = $vertex->type();

        switch ($type) {
            case VertexType::TYPE_COMMAND:
                $class = Command::class;
                break;
            case VertexType::TYPE_AGGREGATE:
                $class = Aggregate::class;
                break;
            case VertexType::TYPE_EVENT:
                $class = Event::class;
                break;
            case VertexType::TYPE_DOCUMENT:
                $class = Document::class;
                break;
            case VertexType::TYPE_POLICY:
                $class = Policy::class;
                break;
            case VertexType::TYPE_EXTERNAL_SYSTEM:
                $class = ExternalSystem::class;
                break;
            case VertexType::TYPE_HOT_SPOT:
                $class = HotSpot::class;
                break;
            case VertexType::TYPE_ROLE:
                $class = Role::class;
                break;
            case VertexType::TYPE_UI:
                $class = Ui::class;
                break;
            case VertexType::TYPE_FEATURE:
                $class = Feature::class;
                break;
            case VertexType::TYPE_BOUNDED_CONTEXT:
                $class = BoundedContext::class;
                break;
            default:
                throw new RuntimeException(\sprintf('Given type "%s" is not supported', $type));
        }

        $self = new $class(
            $vertex->id(),
            $type,
            $label,
            ($filterName)($label)
        );

        $self->init($vertex, $filterName, $metadataFactory);

        return $self;
    }

    protected function __construct(string $id, string $type, string $label, string $name)
    {
        if ($type !== static::TYPE) { // @phpstan-ignore-line
            // @phpstan-ignore-next-line
            throw new RuntimeException(\sprintf('Wrong vertex type "%s" provided. Vertex type must be "%s".', $type, static::TYPE));
        }

        $this->id = $id;
        $this->type = $type;
        $this->label = $label;
        $this->name = $name;
    }

    /**
     * Can be overridden to retrieve more vertex attributes
     *
     * @param Node $vertex
     * @param callable $filterName
     * @param callable|null $metadataFactory
     */
    protected function init(
        Node $vertex,
        callable $filterName,
        ?callable $metadataFactory
    ): void {
        $this->metadata = $vertex->metadata();

        if (null !== $metadataFactory) {
            $this->metadataInstance = $metadataFactory($vertex, $filterName);

            if (! $this->metadataInstance instanceof Metadata) {
                throw new RuntimeException(\sprintf('Metadata instance must implement \EventEngine\InspectioGraph\Metadata\Metadata interface.'));
            }
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function metadata(): ?string
    {
        return $this->metadata;
    }

    public function merge(VertexType $vertex): void
    {
        if ($vertex->id() !== $this->id()) {
            throw new RuntimeException(
                \sprintf('Can not merge vertex due different ids. Id is "%s" but got "%s"', $this->id, $vertex->id())
            );
        }
        if ($vertex->type() !== $this->type()) {
            throw new RuntimeException(
                \sprintf('Can not merge vertex due different types. Type is "%s" but got "%s"', $this->type, $vertex->type())
            );
        }

        $this->metadataInstance = $vertex->metadataInstance();
        $this->metadata = $vertex->metadata();
        $this->name = $vertex->name();
        $this->label = $vertex->label();
    }
}
