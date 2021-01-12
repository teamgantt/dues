<?php

namespace TeamGantt\Dues\Model\Transaction;

use Spatie\Enum\Enum;

/**
 * @method static self creditCard()
 * @method static self paypalAccount()
 * @method static self unknown()
 * @method static self initialized()
 */
class PaymentInstrumentType extends Enum
{
}
