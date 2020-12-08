<?php

namespace TeamGantt\Dues\Model;

abstract class Entity
{
    protected string $id;

    public function __construct(string $id = '')
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return empty($this->getId());
    }

    public function isEqualTo(Entity $other): bool
    {
        return $other->getId() === $this->getId();
    }
}
