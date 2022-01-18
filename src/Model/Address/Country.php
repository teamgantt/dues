<?php

namespace TeamGantt\Dues\Model\Address;

use Spatie\Enum\Enum;

/**
 * Two-character code representing a country. Full list can be found here:
 * https://developers.braintreepayments.com/reference/general/countries/php#list-of-countries.
 *
 * @method static self AF()
 * @method static self AX()
 * @method static self AL()
 * @method static self DZ()
 * @method static self AS()
 * @method static self AD()
 * @method static self AO()
 * @method static self AI()
 * @method static self AQ()
 * @method static self AG()
 * @method static self AR()
 * @method static self AM()
 * @method static self AW()
 * @method static self AU()
 * @method static self AT()
 * @method static self AZ()
 * @method static self BS()
 * @method static self BH()
 * @method static self BD()
 * @method static self BB()
 * @method static self BY()
 * @method static self BE()
 * @method static self BZ()
 * @method static self BJ()
 * @method static self BM()
 * @method static self BT()
 * @method static self BO()
 * @method static self BQ()
 * @method static self BA()
 * @method static self BW()
 * @method static self BV()
 * @method static self BR()
 * @method static self IO()
 * @method static self BN()
 * @method static self BG()
 * @method static self BF()
 * @method static self BI()
 * @method static self KH()
 * @method static self CM()
 * @method static self CA()
 * @method static self CV()
 * @method static self KY()
 * @method static self CF()
 * @method static self TD()
 * @method static self CL()
 * @method static self CN()
 * @method static self CX()
 * @method static self CC()
 * @method static self CO()
 * @method static self KM()
 * @method static self CG()
 * @method static self CD()
 * @method static self CK()
 * @method static self CR()
 * @method static self CI()
 * @method static self HR()
 * @method static self CU()
 * @method static self CW()
 * @method static self CY()
 * @method static self CZ()
 * @method static self DK()
 * @method static self DJ()
 * @method static self DM()
 * @method static self DO()
 * @method static self EC()
 * @method static self EG()
 * @method static self SV()
 * @method static self GQ()
 * @method static self ER()
 * @method static self EE()
 * @method static self ET()
 * @method static self FK()
 * @method static self FO()
 * @method static self FJ()
 * @method static self FI()
 * @method static self FR()
 * @method static self GF()
 * @method static self PF()
 * @method static self TF()
 * @method static self GA()
 * @method static self GM()
 * @method static self GE()
 * @method static self DE()
 * @method static self GH()
 * @method static self GI()
 * @method static self GR()
 * @method static self GL()
 * @method static self GD()
 * @method static self GP()
 * @method static self GU()
 * @method static self GT()
 * @method static self GG()
 * @method static self GN()
 * @method static self GW()
 * @method static self GY()
 * @method static self HT()
 * @method static self HM()
 * @method static self HN()
 * @method static self HK()
 * @method static self HU()
 * @method static self IS()
 * @method static self IN()
 * @method static self ID()
 * @method static self IR()
 * @method static self IQ()
 * @method static self IE()
 * @method static self IM()
 * @method static self IL()
 * @method static self IT()
 * @method static self JM()
 * @method static self JP()
 * @method static self JE()
 * @method static self JO()
 * @method static self KZ()
 * @method static self KE()
 * @method static self KI()
 * @method static self KP()
 * @method static self KR()
 * @method static self KW()
 * @method static self KG()
 * @method static self LA()
 * @method static self LV()
 * @method static self LB()
 * @method static self LS()
 * @method static self LR()
 * @method static self LY()
 * @method static self LI()
 * @method static self LT()
 * @method static self LU()
 * @method static self MO()
 * @method static self MK()
 * @method static self MG()
 * @method static self MW()
 * @method static self MY()
 * @method static self MV()
 * @method static self ML()
 * @method static self MT()
 * @method static self MH()
 * @method static self MQ()
 * @method static self MR()
 * @method static self MU()
 * @method static self YT()
 * @method static self MX()
 * @method static self FM()
 * @method static self MD()
 * @method static self MC()
 * @method static self MN()
 * @method static self ME()
 * @method static self MS()
 * @method static self MA()
 * @method static self MZ()
 * @method static self MM()
 * @method static self NA()
 * @method static self NR()
 * @method static self NP()
 * @method static self NL()
 * @method static self NC()
 * @method static self NZ()
 * @method static self NI()
 * @method static self NE()
 * @method static self NG()
 * @method static self NU()
 * @method static self NF()
 * @method static self MP()
 * @method static self NO()
 * @method static self OM()
 * @method static self PK()
 * @method static self PW()
 * @method static self PS()
 * @method static self PA()
 * @method static self PG()
 * @method static self PY()
 * @method static self PE()
 * @method static self PH()
 * @method static self PN()
 * @method static self PL()
 * @method static self PT()
 * @method static self PR()
 * @method static self QA()
 * @method static self RE()
 * @method static self RO()
 * @method static self RU()
 * @method static self RW()
 * @method static self BL()
 * @method static self SH()
 * @method static self KN()
 * @method static self LC()
 * @method static self MF()
 * @method static self PM()
 * @method static self VC()
 * @method static self WS()
 * @method static self SM()
 * @method static self ST()
 * @method static self SA()
 * @method static self SN()
 * @method static self RS()
 * @method static self SC()
 * @method static self SL()
 * @method static self SG()
 * @method static self SX()
 * @method static self SK()
 * @method static self SI()
 * @method static self SB()
 * @method static self SO()
 * @method static self ZA()
 * @method static self GS()
 * @method static self SS()
 * @method static self ES()
 * @method static self LK()
 * @method static self SD()
 * @method static self SR()
 * @method static self SJ()
 * @method static self SZ()
 * @method static self SE()
 * @method static self CH()
 * @method static self SY()
 * @method static self TW()
 * @method static self TJ()
 * @method static self TZ()
 * @method static self TH()
 * @method static self TL()
 * @method static self TG()
 * @method static self TK()
 * @method static self TO()
 * @method static self TT()
 * @method static self TN()
 * @method static self TR()
 * @method static self TM()
 * @method static self TC()
 * @method static self TV()
 * @method static self UG()
 * @method static self UA()
 * @method static self AE()
 * @method static self GB()
 * @method static self UM()
 * @method static self US()
 * @method static self UY()
 * @method static self UZ()
 * @method static self VU()
 * @method static self VA()
 * @method static self VE()
 * @method static self VN()
 * @method static self VG()
 * @method static self VI()
 * @method static self WF()
 * @method static self EH()
 * @method static self YE()
 * @method static self ZM()
 * @method static self ZW()
 */
class Country extends Enum
{
}