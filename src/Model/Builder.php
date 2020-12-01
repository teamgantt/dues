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
     *
     * @return Builder
     */
    protected function with(string $k, $v): self
    {
        $this->data[$k] = $v;

        return $this;
    }

    protected function getId(): ?string
    {
        return isset($this->data['id']) ? $this->data['id'] : null;
    }

    abstract public function build(): Entity;
}
