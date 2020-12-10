<?php

namespace TeamGantt\Dues\Model\Transaction;

use Spatie\Enum\Enum;

/**
 * @method static self authorizationExpired()
 * @method static self authorized()
 * @method static self authorizing()
 * @method static self settlementConfirmed()
 * @method static self settlementPending()
 * @method static self settlementDeclined()
 * @method static self failed()
 * @method static self gatewayRejected()
 * @method static self processorDeclined()
 * @method static self settled()
 * @method static self settling()
 * @method static self submittedForSettlement()
 * @method static self voided()
 * @method static self unrecognized()
 * @method static self initialized()
 */
class Status extends Enum
{
}
