<?php

namespace TeamGantt\Dues\Model\Subscription;

use Spatie\Enum\Enum;

/**
 * @method static self active()
 * @method static self canceled()
 * @method static self expired()
 * @method static self pastDue()
 * @method static self pending()
 */
class Status extends Enum
{
}
