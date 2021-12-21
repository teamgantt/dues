<?php

namespace TeamGantt\Dues\Model;

use Spatie\Enum\Enum;

/**
 * @method static self americanExpress()
 * @method static self carteBlanche()
 * @method static self chinaUnionPay()
 * @method static self discover()
 * @method static self elo()
 * @method static self jcb()
 * @method static self laser()
 * @method static self maestro()
 * @method static self masterCard()
 * @method static self solo()
 * @method static self switch()
 * @method static self ukMaestro()
 * @method static self visa()
 * @method static self unknown()
 * @method static self initialized()
 */
class CreditCardType extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'americanExpress' => 'American Express',
            'carteBlanche' => 'Carte Blanche',
            'chinaUnionPay' => 'China UnionPay',
            'discover' => 'Discover',
            'elo' => 'Elo',
            'jcb' => 'JCB',
            'laser' => 'Laser',
            'maestro' => 'Maestro',
            'masterCard' => 'MasterCard',
            'solo' => 'Solo',
            'switch' => 'Switch',
            'ukMaestro' => 'UK Maestro',
            'visa' => 'Visa',
            'unknown' => 'Unknown',
        ];
    }
}
