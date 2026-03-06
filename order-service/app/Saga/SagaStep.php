<?php

namespace App\Saga;

abstract class SagaStep
{
    abstract public function getName(): string;

    /**
     * Execute the step. Return context updates (merged into saga context).
     */
    abstract public function execute(array &$context): array;

    /**
     * Compensating transaction: undo the step's effects.
     */
    abstract public function compensate(array $context): void;
}
