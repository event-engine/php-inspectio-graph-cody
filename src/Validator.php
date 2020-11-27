<?php

/**
 * @see       https://github.com/event-engine/php-inspectio-graph-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-inspectio-graph-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-inspectio-graph-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\InspectioGraphCody;

use EventEngine\InspectioGraphCody\Constraint\Exception\ConstraintException;

class Validator
{
    /**
     * @var callable
     */
    private $constraint;

    /**
     * @var ConstraintException[]
     */
    private $errors = [];

    public function __construct(callable $constraint)
    {
        $this->constraint = $constraint;
    }

    public function isValid(Node $graph): bool
    {
        $isValid = true;

        // TODO implement

        return $isValid;
    }

    /**
     * @return ConstraintException[]
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
