<?php

namespace App\Libs;

class Utility
{
    const GEOIPCOUNTRY = array('AD' => 'Andorra', 'AE' => 'United Arab Emirates', 'AF' => 'Afghanistan', 'AG' => 'Antigua and Barbuda', 'AI' => 'Anguilla'
    , 'AL' => 'Albania', 'AM' => 'Armenia', 'AO' => 'Angola', 'AP' => 'Non-spec Asia Pas Location', 'AR' => 'Argentina'
    , 'AS' => 'American Samoa', 'AT' => 'Austria', 'AU' => 'Australia', 'AW' => 'Aruba', 'AX' => 'Aland Islands'
    , 'AZ' => 'Azerbaijan', 'BA' => 'Bosnia and Herzegowina', 'BB' => 'Barbados', 'BD' => 'Bangladesh', 'BE' => 'Belgium'
    , 'BF' => 'Burkina Faso', 'BG' => 'Bulgaria', 'BH' => 'Bahrain', 'BI' => 'Burundi', 'BJ' => 'Benin'
    , 'BM' => 'Bermuda', 'BN' => 'Brunei Darussalam', 'BO' => 'Bolivia', 'BQ' => 'Bonaire; Sint Eustatius; Saba', 'BR' => 'Brazil'
    , 'BS' => 'Bahamas', 'BT' => 'Bhutan', 'BW' => 'Botswana', 'BY' => 'Belarus', 'BZ' => 'Belize'
    , 'CA' => 'Canada', 'CD' => 'Congo The Democratic Republic of The', 'CF' => 'Central African Republic', 'CG' => 'Congo', 'CH' => 'Switzerland'
    , 'CI' => 'Cote D\'ivoire', 'CK' => 'Cook Islands', 'CL' => 'Chile', 'CM' => 'Cameroon', 'CN' => 'China'
    , 'CO' => 'Colombia', 'CR' => 'Costa Rica', 'CU' => 'Cuba', 'CV' => 'Cape Verde', 'CW' => 'Curacao'
    , 'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DE' => 'Germany', 'DJ' => 'Djibouti', 'DK' => 'Denmark'
    , 'DM' => 'Dominica', 'DO' => 'Dominican Republic', 'DZ' => 'Algeria', 'EC' => 'Ecuador', 'EE' => 'Estonia'
    , 'EG' => 'Egypt', 'ER' => 'Eritrea', 'ES' => 'Spain', 'ET' => 'Ethiopia', 'EU' => 'European Union'
    , 'FI' => 'Finland', 'FJ' => 'Fiji', 'FK' => 'Falkland Islands (MALVINAS)', 'FM' => 'Micronesia Federated States of', 'FO' => 'Faroe Islands'
    , 'FR' => 'France', 'GA' => 'Gabon', 'GB' => 'United Kingdom', 'GD' => 'Grenada', 'GE' => 'Georgia'
    , 'GF' => 'French Guiana', 'GG' => 'Guernsey', 'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GL' => 'Greenland'
    , 'GM' => 'Gambia', 'GN' => 'Guinea', 'GP' => 'Guadeloupe', 'GQ' => 'Equatorial Guinea', 'GR' => 'Greece'
    , 'GT' => 'Guatemala', 'GU' => 'Guam', 'GW' => 'Guinea-bissau', 'GY' => 'Guyana', 'HK' => 'Hong Kong'
    , 'HN' => 'Honduras', 'HR' => 'Croatia (LOCAL Name: Hrvatska)', 'HT' => 'Haiti', 'HU' => 'Hungary', 'ID' => 'Indonesia'
    , 'IE' => 'Ireland', 'IL' => 'Israel', 'IM' => 'Isle of Man', 'IN' => 'India', 'IO' => 'British Indian Ocean Territory'
    , 'IQ' => 'Iraq', 'IR' => 'Iran (ISLAMIC Republic Of)', 'IS' => 'Iceland', 'IT' => 'Italy', 'JE' => 'Jersey'
    , 'JM' => 'Jamaica', 'JO' => 'Jordan', 'JP' => 'Japan', 'KE' => 'Kenya', 'KG' => 'Kyrgyzstan'
    , 'KH' => 'Cambodia', 'KI' => 'Kiribati', 'KM' => 'Comoros', 'KN' => 'Saint Kitts and Nevis', 'KP' => 'Korea Democratic People\'s Republic of'
    , 'KR' => 'Korea Republic of', 'KW' => 'Kuwait', 'KY' => 'Cayman Islands', 'KZ' => 'Kazakhstan', 'LA' => 'Lao People\'s Democratic Republic'
    , 'LB' => 'Lebanon', 'LC' => 'Saint Lucia', 'LI' => 'Liechtenstein', 'LK' => 'Sri Lanka', 'LR' => 'Liberia'
    , 'LS' => 'Lesotho', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'LV' => 'Latvia', 'LY' => 'Libyan Arab Jamahiriya'
    , 'MA' => 'Morocco', 'MC' => 'Monaco', 'MD' => 'Moldova Republic of', 'ME' => 'Montenegro', 'MF' => 'Saint Martin'
    , 'MG' => 'Madagascar', 'MH' => 'Marshall Islands', 'MK' => 'Macedonia', 'ML' => 'Mali', 'MM' => 'Myanmar'
    , 'MN' => 'Mongolia', 'MO' => 'Macau', 'MP' => 'Northern Mariana Islands', 'MQ' => 'Martinique', 'MR' => 'Mauritania'
    , 'MS' => 'Montserrat', 'MT' => 'Malta', 'MU' => 'Mauritius', 'MV' => 'Maldives', 'MW' => 'Malawi'
    , 'MX' => 'Mexico', 'MY' => 'Malaysia', 'MZ' => 'Mozambique', 'NA' => 'Namibia', 'NC' => 'New Caledonia'
    , 'NE' => 'Niger', 'NF' => 'Norfolk Island', 'NG' => 'Nigeria', 'NI' => 'Nicaragua', 'NL' => 'Netherlands'
    , 'NO' => 'Norway', 'NP' => 'Nepal', 'NR' => 'Nauru', 'NU' => 'Niue', 'NZ' => 'New Zealand'
    , 'OM' => 'Oman', 'PA' => 'Panama', 'PE' => 'Peru', 'PF' => 'French Polynesia', 'PG' => 'Papua New Guinea'
    , 'PH' => 'Philippines', 'PK' => 'Pakistan', 'PL' => 'Poland', 'PM' => 'St. Pierre and Miquelon', 'PR' => 'Puerto Rico'
    , 'PS' => 'Palestinian Territory Occupied', 'PT' => 'Portugal', 'PW' => 'Palau', 'PY' => 'Paraguay', 'QA' => 'Qatar'
    , 'RE' => 'Reunion', 'RO' => 'Romania', 'RS' => 'Serbia', 'RU' => 'Russian Federation', 'RW' => 'Rwanda'
    , 'SA' => 'Saudi Arabia', 'SB' => 'Solomon Islands', 'SC' => 'Seychelles', 'SD' => 'Sudan', 'SE' => 'Sweden'
    , 'SG' => 'Singapore', 'SI' => 'Slovenia', 'SK' => 'Slovakia (SLOVAK Republic)', 'SL' => 'Sierra Leone', 'SM' => 'San Marino'
    , 'SN' => 'Senegal', 'SO' => 'Somalia', 'SR' => 'Suriname', 'SS' => 'South Sudan', 'ST' => 'Sao Tome and Principe'
    , 'SV' => 'El Salvador', 'SX' => 'Sint Maarten', 'SY' => 'Syrian Arab Republic', 'SZ' => 'Swaziland', 'TC' => 'Turks and Caicos Islands'
    , 'TD' => 'Chad', 'TG' => 'Togo', 'TH' => 'Thailand', 'TJ' => 'Tajikistan', 'TK' => 'Tokelau'
    , 'TL' => 'Timor-leste', 'TM' => 'Turkmenistan', 'TN' => 'Tunisia', 'TO' => 'Tonga', 'TR' => 'Turkey'
    , 'TT' => 'Trinidad and Tobago', 'TV' => 'Tuvalu', 'TW' => 'Taiwan; Republic of China (ROC)', 'TZ' => 'Tanzania United Republic of', 'UA' => 'Ukraine'
    , 'UG' => 'Uganda', 'US' => 'United States', 'UY' => 'Uruguay', 'UZ' => 'Uzbekistan', 'VA' => 'Holy See (VATICAN City State)'
    , 'VC' => 'Saint Vincent and The Grenadines', 'VE' => 'Venezuela', 'VG' => 'Virgin Islands (BRITISH)', 'VI' => 'Virgin Islands (U.S.)', 'VN' => 'Viet Nam'
    , 'VU' => 'Vanuatu', 'WF' => 'Wallis and Futuna Islands', 'WS' => 'Samoa', 'YE' => 'Yemen', 'YT' => 'Mayotte'
    , 'ZA' => 'South Africa', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe', 'ZZ' => 'Reserved');

    public function __construct()
    {

    }

    public function randomHexString($length)
    {
        $chars = "0123456789abcdef";

        $size = strlen($chars);
        $str = '';

        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[rand(0, $size - 1)];
        }

        return $str;
    }

    public function randomStringOnlyUppercase($length)
    {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

        $size = strlen($chars);
        $str = '';

        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[rand(0, $size - 1)];
        }

        return $str;
    }


    public function random($length = 6, $numeric = 0)
    {
        PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
        if ($numeric) {
            $hash = '';
            $chars = '123456789';
            $max = strlen($chars) - 1;
            for ($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        } else {
            $hash = '';
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789abcdefghjkmnpqrstuvwxyz';
            $max = strlen($chars) - 1;
            for ($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }
        return $hash;
    }

    /**
     * Checks if the given string is an address
     *
     * @method isAddress
     * @param {String} $address the given HEX adress
     * @return {Boolean}
     */
    public function isEthereumAddress($address)
    {
        if (!preg_match('/^(0x)?[0-9a-f]{40}$/i', $address)) {
            // check if it has the basic requirements of an address
            return false;
        } elseif (!preg_match('/^(0x)?[0-9a-f]{40}$/', $address) || preg_match('/^(0x)?[0-9A-F]{40}$/', $address)) {
            // If it's all small caps or all all caps, return true
            return true;
        } else {
            // Otherwise check each case
            return true;
            //return $this->isChecksumAddress($address);
        }
    }

    /**
     * Checks if the given string is a checksummed address
     *
     * @method isChecksumAddress
     * @param {String} $address the given HEX adress
     * @return {Boolean}
     */
    public function isChecksumAddress($address)
    {

        // Check each case
        $address = str_replace('0x', '', $address);
        $addressHash = hash('sha3', strtolower($address));
        $addressArray = str_split($address);
        $addressHashArray = str_split($addressHash);

        for ($i = 0; $i < 40; $i++) {
            // the nth letter should be uppercase if the nth digit of casemap is 1
            if ((intval($addressHashArray[$i], 16) > 7 && strtoupper($addressArray[$i]) !== $addressArray[$i]) || (intval($addressHashArray[$i], 16) <= 7 && strtolower($addressArray[$i]) !== $addressArray[$i])) {
                return false;
            }
        }
        return true;
    }

    function curl_get_contents($url)
    {
        // Initiate the curl session
        $ch = curl_init();
        // Set the URL
        curl_setopt($ch, CURLOPT_URL, $url);
        // Removes the headers from the output
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // Return the output instead of displaying it directly
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // Execute the curl session
        $output = curl_exec($ch);
        // Close the curl session
        curl_close($ch);
        // Return the output as a variable

        return json_decode($output);
    }

    function curl_post($url, $postFields = array(), $headerJson = true)
    {
        // Initiate the curl session
        $ch = curl_init();
        // Set the URL
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($headerJson) {
            // Removes the headers from the output
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
        } else {
            $post_items = array();
            foreach ($postFields as $key => $value) {
                $post_items[] = $key . '=' . $value;
            }
            $post_string = implode('&', $post_items);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        }
        // Return the output instead of displaying it directly
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_POST, 1);
        // Execute the curl session
        $output = curl_exec($ch);
        // Close the curl session

        $info = curl_getinfo($ch);
        curl_close($ch);
        // Return the output as a variable
        return json_decode($output);
    }

    function curl_put($url, $putFields = array())
    {
        // Initiate the curl session
        $ch = curl_init();
        // Set the URL
        curl_setopt($ch, CURLOPT_URL, $url);
        // Removes the headers from the output
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        // Return the output instead of displaying it directly
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($putFields));

        // Execute the curl session
        $output = curl_exec($ch);
        // Close the curl session
        curl_close($ch);
        // Return the output as a variable
        return json_decode($output);
    }


    function bcdechex($dec)
    {
        $hex = '';
        do {
            $last = bcmod($dec, 16);
            $hex = dechex($last) . $hex;
            $dec = bcdiv(bcsub($dec, $last), 16);
        } while ($dec > 0);
        return $hex;
    }

    function convertHex($amount, $decimals = 8)
    {
        if ($decimals <= 8) {
            $dec = number_format($amount, $decimals, '', '');
        } else {
            $dec = number_format($amount, 8, '', '');
            for ($i = 0; $i < $decimals - 8; $i++) {
                $dec .= '0';
            }
        }

        return $this->bcdechex($dec);
    }


    function curl_post_forwaves($url, $postFields = array(), $headerJson = true)
    {
        // Initiate the curl session
        $ch = curl_init();
        // Set the URL
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json', 'API_KEY: Sistemkoin_API_KEY'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
        // Return the output instead of displaying it directly
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_POST, 1);
        // Execute the curl session
        $output = curl_exec($ch);
        // Close the curl session

        $info = curl_getinfo($ch);
        curl_close($ch);
        // Return the output as a variable
        return json_decode($output);
    }

    /**
     * Throws exception if validation is failed
     *
     * @param bool   $pFailed
     * @param string $pErrMsg
     *
     * @throws \RuntimeException
     */
    protected function validate($pFailed, $pErrMsg)
    {
        if ($pFailed)
        {
            throw new \RuntimeException($pErrMsg);
        }
    }

    /**
     * Getting JSON last error message
     * Function json_last_error_msg exists from PHP 5.5
     *
     * @return string
     */
    function getJsonLastErrorMsg()
    {
        if (!function_exists('json_last_error_msg'))
        {
            function json_last_error_msg()
            {
                static $errors = array(
                    JSON_ERROR_NONE           => 'No error',
                    JSON_ERROR_DEPTH          => 'Maximum stack depth exceeded',
                    JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
                    JSON_ERROR_CTRL_CHAR      => 'Unexpected control character found',
                    JSON_ERROR_SYNTAX         => 'Syntax error',
                    JSON_ERROR_UTF8           => 'Malformed UTF-8 characters, possibly incorrectly encoded'
                );
                $error = json_last_error();
                return array_key_exists($error, $errors) ? $errors[$error] : 'Unknown error (' . $error . ')';
            }
        }

        // Fix PHP 5.2 error caused by missing json_last_error function
        if (function_exists('json_last_error'))
        {
            return json_last_error() ? json_last_error_msg() : null;
        }
        else
        {
            return null;
        }
    }

    function ont_sigsvr_post($url, $postFields = array())
    {
        // Initiate the curl session
        $ch = curl_init();
        // Set the URL
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
        // Return the output instead of displaying it directly
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_POST, 1);
        // Execute the curl session
        $output = curl_exec($ch);
        // Close the curl session

        $info = curl_getinfo($ch);
        curl_close($ch);
        // Return the output as a variable
        $responseDecoded = json_decode($output, true);

        // check if decoding json generated any errors
        $jsonErrorMsg = $this->getJsonLastErrorMsg();
        $this->validate( !is_null($jsonErrorMsg), $jsonErrorMsg);
        // check if response is correct

//        if (!$this->is_ripple) {
//            $this->validate(empty($responseDecoded['id']), 'Invalid response data structure: ' . $responseMessage);
//            $this->validate($responseDecoded['id'] != $requestId, 'Request id: ' . $requestId . ' is different from Response id: ' . $responseDecoded['id']);
//        }
        if (isset($responseDecoded['error_code']) && $responseDecoded['error_code'] != 0)
        {
            $errorMessage = 'Request have return error: ' . $responseDecoded['error_info'] . '; ';
            $this->validate( !is_null($responseDecoded['error_code']), $errorMessage);
        }
        return $responseDecoded['result'];
    }

    function tgn_curl_post($url, $postFields = array(), $includeAuth = false, $auth = '') {
        // Initiate the curl session
        $ch = curl_init();
        // Set the URL
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
        if ($includeAuth) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json', 'Authorization: '.$auth));
        }
        // Return the output instead of displaying it directly
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_POST, 1);
        // Execute the curl session
        $output = curl_exec($ch);
        // Close the curl session

        $info = curl_getinfo($ch);
        curl_close($ch);
        // Return the output as a variable
        return json_decode($output);
    }
}