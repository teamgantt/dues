<?php

namespace TeamGantt\Dues\Model;

abstract class Builder
{
    /**
     * @var mixed[]
     */
    protected array $data = [];

    /**
     * @param mixed $v
     */
    protected function with(string $k, $v): self
    {
        $this->data[$k] = $v;

        return $this;
    }

    protected function getId(): string
    {
        return isset($this->data['id']) ? $this->data['id'] : '';
    }

    protected function reset(): void
    {
        $this->data = [];
    }

    abstract public function build(): Entity;
}
