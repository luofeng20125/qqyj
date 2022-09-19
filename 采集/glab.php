<?php
/**
 *Created.by.PhpStorm.
 *User:feng
 *Date:2022/9/19
 *Time:21:23
 */
function getHtml($url)
{
//    $opts = [
//        'http' => [
//            'method' => 'GET',
//            'header' =>
//                "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n".
//                "Cookie: \r\n".
//                "Pragma:no-cache\r\n",
//        ],
//    ];
//    $context = stream_context_create($opts);

    $url = preg_replace('# #', '%20', $url);
//    $result_data = file_get_contents($url, false, $context);
    $result_data = file_get_contents($url, false);

    return $result_data;

}
$res = getHtml('https://www.nowmsg.com/findzip/in_postal_code.asp');
file_put_contents(time().'.php',$res,true);