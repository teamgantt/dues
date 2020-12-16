<?php

namespace TeamGantt\Dues\Contracts;

interface EventListenerContainer
{
    public function addListener(EventListener $listener): void;

    public function removeListener(EventListener $listener): void;
}
