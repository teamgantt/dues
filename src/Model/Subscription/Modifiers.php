<?php

namespace TeamGantt\Dues\Model\Subscription;

use ArrayAccess;
use Countable;
use DomainException;
use TeamGantt\Dues\Contracts\Arrayable;
use TeamGantt\Dues\Model\Subscription;

/**
 * @implements \ArrayAccess<int, Modifier>
 */
class Modifiers implements Arrayable, ArrayAccess, Countable
{
    private Subscription $subscription;

    /**
     * @var Modifier[]
     */
    private array $new = [];

    /**
     * @var Modifier[]
     */
    private array $current = [];

    /**
     * @var string[]
     */
    private array $removed = [];

    /**
     * @param Modifier[] $current
     *
     * @return void
     */
    public function __construct(Subscription $subscription, $current = [])
    {
        $this->subscription = $subscription;
        foreach ($current as $modifier) {
            $this->current[$modifier->getId()] = $modifier;
        }
    }

    public function add(Modifier $modifier): void
    {
        $key = $modifier->getId();

        if ($this->subscription->isNew()) {
            $this->new[$key] = $modifier;
        } elseif (isset($this->current[$key])) {
            $this->current[$key] = $modifier;
        } else {
            $this->new[$key] = $modifier;
        }

        unset($this->removed[$key]);
    }

    public function remove(string $id): void
    {
        unset($this->new[$id]);
        unset($this->current[$id]);
        $this->removed[$id] = $id;
    }

    /**
     * @param Modifier[] $modifiers
     */
    public function set(array $modifiers): void
    {
        foreach ($modifiers as $modifier) {
            $this->add($modifier);
        }
    }

    /**
     * @return Modifier[]
     */
    public function getNew(): array
    {
        return array_values($this->new);
    }

    /**
     * @return Modifier[]
     */
    public function getCurrent(): array
    {
        return array_values($this->current);
    }

    /**
     * @return string[]
     */
    public function getRemoved(): array
    {
        return array_values($this->removed);
    }

    /**
     * @return Modifier[]
     */
    public function getAll(): array
    {
        return array_merge(
            $this->getNew(),
            $this->getCurrent()
        );
    }

    public function toArray(): array
    {
        $array = [
           'new' => array_map(fn (Modifier $m) => $m->toArray(), $this->getNew()),
           'current' => array_map(fn (Modifier $m) => $m->toArray(), $this->getCurrent()),
           'removed' => array_values($this->getRemoved()),
       ];

        $results = [];
        foreach ($array as $key => $value) {
            if (!empty($value)) {
                $results[$key] = $value;
            }
        }

        return $results;
    }

    public function offsetExists($offset)
    {
        $all = $this->getAll();

        return isset($all[$offset]);
    }

    public function offsetGet($offset)
    {
        $all = $this->getAll();

        return $all[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        throw new DomainException('Cannot set Modifier by offset');
    }

    public function offsetUnset($offset)
    {
        throw new DomainException('Cannot unset Modifier by offset');
    }

    public function count()
    {
        $all = $this->getAll();

        return count($all);
    }
}
