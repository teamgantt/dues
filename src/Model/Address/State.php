<?php

namespace TeamGantt\Dues\Model\Address;

use Spatie\Enum\Enum;

/**
 * @method static self Alabama()
 * @method static self Alaska()
 * @method static self Arizona()
 * @method static self Arkansas()
 * @method static self California()
 * @method static self Colorado()
 * @method static self Connecticut()
 * @method static self Delaware()
 * @method static self Florida()
 * @method static self Georgia()
 * @method static self Hawaii()
 * @method static self Illinois()
 * @method static self Idaho()
 * @method static self Indiana()
 * @method static self Iowa()
 * @method static self Kansas()
 * @method static self Kentucky()
 * @method static self Louisiana()
 * @method static self Maine()
 * @method static self Maryland()
 * @method static self Massachusetts()
 * @method static self Michigan()
 * @method static self Minnesota()
 * @method static self Mississippi()
 * @method static self Missouri()
 * @method static self Montana()
 * @method static self Nebraska()
 * @method static self Nevada()
 * @method static self NewHampshire()
 * @method static self NewJersey()
 * @method static self NewMexico()
 * @method static self NewYork()
 * @method static self NorthCarolina()
 * @method static self NorthDakota()
 * @method static self Ohio()
 * @method static self Oklahoma()
 * @method static self Oregon()
 * @method static self Pennsylvania()
 * @method static self RhodeIsland()
 * @method static self SouthCarolina()
 * @method static self SouthDakota()
 * @method static self Tennessee()
 * @method static self Texas()
 * @method static self Utah()
 * @method static self Vermont()
 * @method static self Virginia()
 * @method static self Washington()
 * @method static self WestVirginia()
 * @method static self Wisconsin()
 * @method static self Wyoming()
 * @method static self DistrictOfColumbia()
 * @method static self AmericanSamoa()
 * @method static self Guam()
 * @method static self NorthernMarianaIslands()
 * @method static self PuertoRico()
 * @method static self UnitedStatesMinorOutlyingIslands()
 * @method static self VirginIslands()
 */
class State extends Enum
{
    protected static function values(): array
    {
        return [
            'Alabama' => 'AL',
            'Alaska' => 'AK',
            'Arizona' => 'AZ',
            'Arkansas' => 'AR',
            'California' => 'CA',
            'Colorado' => 'CO',
            'Connecticut' => 'CT',
            'Delaware' => 'DE',
            'Florida' => 'FL',
            'Georgia' => 'GA',
            'Hawaii' => 'HI',
            'Illinois' => 'IL',
            'Idaho' => 'ID',
            'Indiana' => 'IN',
            'Iowa' => 'IA',
            'Kansas' => 'KS',
            'Kentucky' => 'KY',
            'Louisiana' => 'LA',
            'Maine' => 'ME',
            'Maryland' => 'MD',
            'Massachusetts' => 'MA',
            'Michigan' => 'MI',
            'Minnesota' => 'MN',
            'Mississippi' => 'MS',
            'Missouri' => 'MO',
            'Montana' => 'MT',
            'Nebraska' => 'NE',
            'Nevada' => 'NV',
            'NewHampshire' => 'NH',
            'NewJersey' => 'NJ',
            'NewMexico' => 'NM',
            'NewYork' => 'NY',
            'NorthCarolina' => 'NC',
            'NorthDakota' => 'ND',
            'Ohio' => 'OH',
            'Oklahoma' => 'OK',
            'Oregon' => 'OR',
            'Pennsylvania' => 'PA',
            'RhodeIsland' => 'RI',
            'SouthCarolina' => 'SC',
            'SouthDakota' => 'SD',
            'Tennessee' => 'TN',
            'Texas' => 'TX',
            'Utah' => 'UT',
            'Vermont' => 'VT',
            'Virginia' => 'VA',
            'Washington' => 'WA',
            'WestVirginia' => 'WV',
            'Wisconsin' => 'WI',
            'Wyoming' => 'WY',
            'DistrictOfColumbia' => 'DC',
            'AmericanSamoa' => 'AS',
            'Guam' => 'GU',
            'NorthernMarianaIslands' => 'MP',
            'PuertoRico' => 'PR',
            'UnitedStatesMinorOutlyingIslands' => 'UM',
            'VirginIslands' => 'VI',
        ];
    }

    protected static function labels(): array
    {
        return [
            'VirginIslands' => 'U.S. Virgin Islands',
            'UnitedStatesMinorOutlyingIslands' => 'United States Minor Outlying Islands',
            'PuertoRico' => 'Puerto Rico',
            'NorthernMarianaIslands' => 'Northern Mariana Islands',
            'AmericanSamoa' => 'American Samoa',
            'DistrictOfColumbia' => 'District of Columbia',
            'WestVirginia' => 'West Virginia',
            'SouthDakota' => 'South Dakota',
            'SouthCarolina' => 'South Carolina',
            'RhodeIsland' => 'Rhode Island',
            'NorthDakota' => 'North Dakota',
            'NorthCarolina' => 'North Carolina',
            'NewYork' => 'New York',
            'NewMexico' => 'New Mexico',
            'NewJersey' => 'New Jersey',
            'NewHampshire' => 'New Hampshire',
        ];
    }
}
