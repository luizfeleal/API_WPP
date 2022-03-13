<?php

$api_wpp = array(
    'number' => "5521964159100@c.us",
    'bodyMessage' => "Olá, Anderson aqui! Estou vindo dizer que sua inscrição ja foi criada, baixe nosso app e ja comece a usar. Te espero no portal :)"
  );

$data = json_encode($api_wpp);
$arquivo = file_get_contents('php://input');

      $json = json_decode($arquivo);

$url = 'https://apiwpp.herokuapp.com/send-message';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"number\": \"$json->number\", \"bodyMessage\": \"Olá, Anderson aqui! Estou vindo dizer que sua inscrição ja foi criada, baixe nosso app e ja comece a usar. Te espero no portal :)\"}");
        $headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $response = json_decode($result);

        //var_dump(json_decode($data));
        //var_dump("SEM json" . $api_wpp);