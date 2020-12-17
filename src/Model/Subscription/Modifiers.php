<?php

namespace TeamGantt\Dues\Model\Subscription;

use TeamGantt\Dues\Model\Modifier\Modifier;
use TeamGantt\Dues\Model\Subscription\Modifier\Operation;
use TeamGantt\Dues\Model\Subscription\Modifier\OperationType;

class Modifiers
{
    /**
     * @var Array<string, Operation>
     */
    private array $operations = [];

    /**
     * @param Modifier[] $modifiers
     */
    public function __construct(array $modifiers = [])
    {
        foreach ($modifiers as $modifier) {
            $this->push($modifier, OperationType::read());
        }
    }

    public function push(Modifier $modifier, OperationType $type): void
    {
        $this->operations[$modifier->getId()] = new Operation($type, $modifier);
    }

    public function drop(Modifier $modifier): void
    {
        unset($this->operations[$modifier->getId()]);
    }

    public function filter(callable $fn): Modifiers
    {
        $modifiers = new Modifiers();

        foreach ($this->operations as $operation) {
            if (!$fn($operation)) {
                continue;
            }
            $modifiers->push($operation->getModifier(), $operation->getType());
        }

        return $modifiers;
    }

    public function get(string $modifierId): ?Operation
    {
        if (!isset($this->operations[$modifierId])) {
            return null;
        }

        return $this->operations[$modifierId];
    }

    /**
     * @return Array<string, Operation>
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @return Modifier[]
     */
    public function toModifierArray(): array
    {
        $modifiers = [];

        foreach ($this->operations as $operation) {
            $modifiers[] = $operation->getModifier();
        }

        return $modifiers;
    }
}
