<?php

namespace TeamGantt\Dues\Model\Subscription;

class AddOnBuilder extends ModifierBuilder
{
    public function build(): AddOn
    {
        $addOn = new AddOn($this->getId());
        $this->buildModifier($addOn);

        return $addOn;
    }
}
