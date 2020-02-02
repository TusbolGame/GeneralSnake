<?php
/**
 * Created by PhpStorm.
 * User: Tall Prince
 * Date: 3/6/2017
 * Time: 6:44 PM
 */

namespace App\Libs;


class SMS
{
    const API_URL = "http://sms.verimor.com.tr/v2/send.json";
    const USER_CODE = "908503041282";
    const USER_PWD = "sistemsa692624";

    private function xml_to_array($xml) {
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if(preg_match_all($reg, $xml, $matches)){
            $count = count($matches[0]);
            for($i = 0; $i < $count; $i++){
                $subxml= $matches[2][$i];
                $key = $matches[1][$i];
                if(preg_match( $reg, $subxml )){
                    $arr[$key] = $this->xml_to_array( $subxml );
                }else{
                    $arr[$key] = $subxml;
                }
            }
        }
        return $arr;
    }

    private function post($curlPost) {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$curlPost);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    public function send($mobile_number, $content) {

        $message = $content.' Sistemkoin\'e giris yapmak icin kullanacagınız tek kullanımlık guvenlik kodudur. ';
        $header = 'Sistemsa';

//        $message = html_entity_decode($message, ENT_COMPAT, "UTF-8");
//        $message = rawurlencode($message);
//
//        $header = html_entity_decode($header, ENT_COMPAT, "UTF-8");
//        $header = rawurlencode($header);

        $sms_msg = array(
            "username" => $this::USER_CODE, // https://oim.verimor.com.tr/sms_settings/edit adresinden öğrenebilirsiniz.
            "password" => $this::USER_PWD, // https://oim.verimor.com.tr/sms_settings/edit adresinden belirlemeniz gerekir.
            "source_addr" => $header, // Gönderici başlığı, https://oim.verimor.com.tr/headers adresinde onaylanmış olmalı, değilse 400 hatası alırsınız.
            "custom_id" => "1424441160.9331344",
            "messages" => array(
                array(
                    "msg" => $message,
                    "dest" => $mobile_number
                )
            )
        );

        // $post_data = $this::API_URL."?usercode=" . $this::USER_CODE . "&password=" . $this::USER_PWD  . "&gsmno={$mobile_number}&message={$message}&msgheader={$header}&startdate=$startdate&stopdate=$stopdate";

        $ch = curl_init($this::API_URL);
        curl_setopt_array($ch, array(
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_POSTFIELDS => json_encode($sms_msg),
        ));
        $http_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($http_code != 200){
            // echo "$http_code $http_response\n";
            return false;
        }
        return $http_response;
    }
}
