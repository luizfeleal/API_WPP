<?php

$params = (object) array();
$params->code = $queryVars['code'];

$key = 'AndersonBrandao';

$textoDescriptografado = base64_decode($params->code);

if($textoDescriptografado != $key) {
  http_response_code(502);
  $result = new stdClass();
  $result->status = 502;
  $result->mensagem = "Houve um erro na autenticação";
  exit(json_encode($result));
}

require('../vendor/vindi/vindi-php/src/WebhookHandler.php');
require('../vendor/autoload.php');

$webhookHandler = new Vindi\WebhookHandler();

$event = $webhookHandler->handle();

// ---> CONFIG FILE VG

$CONFIG_FILE1 = "../configVg.ini";

if(!file_exists($CONFIG_FILE1)) {
    http_response_code(502);
    $result = new stdClass();
    $result->status = 502;
    $result->mensagem = "Falha na configuração do ambiente Vg.";
    exit(json_encode($result));
}

$vg = parse_ini_file('../configVg.ini');

// <--- CONFIG FILE VINDI
    
    $CONFIG_FILE = "../config.ini";

if(!file_exists($CONFIG_FILE)) {
    http_response_code(502);
    $result = new stdClass();
    $result->status = 502;
    $result->mensagem = "Falha na configuração do ambiente V.";
    exit(json_encode($result));
}

$envs = parse_ini_file("../config.ini");

// <-- CONFIG FILE EMAIL
$CONFIG_FILE2 = "../configMail.ini";

if(!file_exists($CONFIG_FILE2)) {
  http_response_code(502);
  $result = new stdClass();
  $result->status = 502;
  $result->mensagem = "Falha na configuração do ambiente mail.";
  exit(json_encode($result));
}

$iniMail = parse_ini_file("../configMail.ini");

require '../vendor/autoload.php';

//Instanciando o Serviço
$vindiService = new VirtualGym\VindiService($envs['CHAVE'], $envs['TARGET']);



  switch($event->type) {
    case 'subscription_canceled':
      $arquivo = file_get_contents('php://input');

      $json = json_decode($arquivo);

      $email = $json->event->data->subscription->customer->email;
      $fullName = $json->event->data->subscription->customer->name;
      $fullName = $json->event->data->subscription->customer->name;
      $id = $json->event->data->subscription->customer->id;
      //$code = $json->event->data->subscription->customer->code;
      //$plano = $json->event->data->subscription->plan->code;

      //$code = $_POST['code'];
      //$firstName = mb_strstr( $nome, ' ', true );
      $name = (explode(" ", $fullName));
      $firstName = array_shift($name);
      $lastName = array_pop($name);

      

        require_once './email/phpmailer/class.phpmailer.php';
        $mail = new PHPMailer();
        $mail->isSMTP();

        // Configurações do servidor de email
        $mail->Host = $iniMail['HOST'];
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';
        $mail->Username = $iniMail['USERNAME'];
        $mail->Password = $iniMail['PASSWD'];//'testecheckout5@gmail.com'//$ini['passwd'];
        $mail->Port = $iniMail['PORT'];

        
        //Configuração da Mensagem
        $emailenviar = $iniMail['USER']; // de onde vai enviar
        $destino = $email;   //email da pessoa que vai receber
        $mail->setFrom($emailenviar); //Remetente
        $mail->addAddress($destino); //Destinatário
        $mail->Subject = '=?UTF-8?B?'.base64_encode("Anderson Brandão - Assinatura cancelada").'?='; //Assunto do e-mail
        //$mail->addBCC('anderson@andersonbrandao.com.br', 'Coach Anderson');

        //$assunto = "Novo e-mail inscrito - Site Coach Anderson"; //assunto do email
        //$assunto = '=?UTF-8?B?'.base64_encode($assunto).'?=';
        
        $arquivo = "
    <div class='container'>
    <div class='content' style='background-color: #FFFFFF;'>
        <div class='header' style='align-items: center; justify-content: center; text-align: center;'>
        <img src='https://www.andersonbrandao.com.br/img/Novo/imgEmail.jpg' alt='Metodo AB>
        </div>
            <div class='titleContent' style='text-align: center;'>
                <div class='title' style='text-align: start;'>
                    <p>Oi $firstName, Anderson Aqui :(</p>
                    <p>Que pena! Sua assinatura foi cancelada!</p>
                    <p>Espero que retorno a ser um assinante no futuro para continuar com uma vida mais saudável</p>
                    <p>Até breve,</p>
                    <p>Tchau,</p>
                    <p>Anderson Brandão</p>
                </div>
            </div>
        </div>
    </div>
				";

        $mail->isHTML(true);
        $mail->Body    = $arquivo;


        if(!$mail->send()) {
            $mgm = "ERRO AO ENVIAR E-MAIL! " . $mail->ErrorInfo;
            echo "Falha ao enviar o email!";
            echo $mail -> ErrorInfo;
        } else {
            $mgm = "E-MAIL ENVIADO COM SUCESSO! Responderemos em breve.";
            echo "Email enviado com sucesso!";
        }

        $paramMetadata = [
          'metadata' => array(
            'emailRenovacaoEnviado' => 'false'
        )
      ];
        
        $atualizaCliente = $vindiService->atualizaCliente($id, $paramMetadata);

        echo('inscrição cancelada');
        
        break;

    case 'subscription_created':
      
      $arquivo = file_get_contents('php://input');

      $json = json_decode($arquivo);

      $email = $json->event->data->subscription->customer->email;
      $fullName = $json->event->data->subscription->customer->name;
      $id = $json->event->data->subscription->customer->id;
      $code = $json->event->data->subscription->customer->code;
      $subscription = $json->event->data->subscription->id;
      $plano = $json->event->data->subscription->plan->code;

      //$code = $_POST['code'];
      //$firstName = mb_strstr( $nome, ' ', true );
      $name = (explode(" ", $fullName));
      $firstName = array_shift($name);
      $lastName = array_pop($name);

      // VERIFICA A DURAÇÃO DO PLANO PARA EXIBIR NO EMAIL.

      $codePlan = $json->event->data->subscription->plan->code;

      $params = [
          'query'    => 'code = "'. $codePlan .'"'
        ];
      $planos = $vindiService->buscaPlanos($params);

      $duration = $planos[0]->metadata->duracao;
    
      // VERIFICA A PARTIR DO EMAIL, SE O USUÁRIO JÁ POSSUI CADASTRO NA VG.

      $customer_data = array(
        "email" => $email
    );
    $data_string = json_encode($customer_data);
    
    $url = $vg['API_URL'] . 'club/' . $vg['CLUB_ID'] . '/member?api_key=' . $vg['API_KEY'] . '&club_secret=' . $vg['CLUB_KEY'] . '&email=' . $email;
    $ch = curl_init($url);
    curl_setopt_array(
        $ch,
        array(
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS => $data_string,
            CURLOPT_RETURNTRANSFER => true
        )
    );
    $resultado = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $resposta = json_decode($resultado);

      //SE O NÚMERO DE USUÁRIOS É MAIOR OU IGUAL A UM

      
      if(count($resposta->result) >= 1){

        // ATUALIZA O CLIENTE, INSERINDO O MEMBER_ID DO CLIENTE EXISTENTE, INSERINDO INFORMAÇÃO DE FALSO PARA O CONTROLE DE ENVIO DE EMAILS.

        $paramMetadata = [
          'metadata' => array(
            'memberId' => $resposta->result[0]->member_id,
            'emailEnviado'=> 'false',
            'emailRenovacaoEnviado' => 'false'
        )
      ];
        
        $atualizaCliente = $vindiService->atualizaCliente($id, $paramMetadata);

        // BUSCA O PARÂMETRO DE EMAIL DE RENOVAÇÃO

        $paramBuscaId = [
          'query'    => 'id = "'. $id .'"' 
      ];
        $buscaCliente = $vindiService->buscarClientes($paramBuscaId);
        $emailRenovacaoEnviado = $buscaCliente[0]->metadata->emailRenovacaoEnviado;
        $phoneNumber = $buscaCliente[0]->phones[0]->number;

        // SE O PARÂMETRO ESTIVER COMO FALSO, ENVIAR EMAIL DE RENOVAÇÃO
        if($emailRenovacaoEnviado == 'false'){

          $api_wpp = array(
            "number" => $phoneNumber . "@c.us",
            "bodyMessage" => "Olá, Anderson aqui! Estou vindo dizer que sua inscrição ja foi criada, baixe nosso app e ja comece a usar. Te espero no portal :)"
          );
          $data_string = json_encode($api_wpp);
        
        $url = 'https://apiwpp.herokuapp.com/send-message';
        $ch = curl_init($url);
        curl_setopt_array(
            $ch,
            array(
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $data_string,
                CURLOPT_RETURNTRANSFER => true
            )
        );
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $response = json_decode($result);
        
        //ENVIA O EMAIL DE RENOVAÇÃO
        require_once './email/phpmailer/class.phpmailer.php';
				$mail = new PHPMailer();
				$mail->isSMTP();

				// Configurações do servidor de email
				$mail->Host = $iniMail['HOST'];
				$mail->SMTPAuth = true;
				$mail->SMTPSecure = 'tls';
				$mail->Username = $iniMail['USERNAME'];
				$mail->Password = $iniMail['PASSWD'];//'testecheckout5@gmail.com'//$ini['passwd'];
				$mail->Port = $iniMail['PORT'];

				
				//Configuração da Mensagem
				$emailenviar = $iniMail['USER']; // de onde vai enviar contato@andersonbrandao.com.br
				$destino = $email;   //email da pessoa que vai receber
				$mail->setFrom($emailenviar); //Remetente
				$mail->addAddress($destino); //Destinatário
        $mail->addAttachment('./files/Contrato.pdf');
				$mail->Subject = '=?UTF-8?B?'.base64_encode("Anderson Brandão - Assinatura renovada").'?='; //Assunto do e-mail
				//$mail->addBCC('anderson@andersonbrandao.com.br', 'Coach Anderson');

				//$assunto = "Novo e-mail inscrito - Site Coach Anderson"; //assunto do email
				//$assunto = '=?UTF-8?B?'.base64_encode($assunto).'?=';
				
				$arquivo = "
        <div>
        <div class='container'>
        <div class='content' style='background-color: #FFFFFF;'>
            <div class='header' style='align-items: center; justify-content: center; text-align: center;'>
            <img src='https://www.andersonbrandao.com.br/img/Novo/imgEmail.jpg' alt='Metodo AB>
            </div>
            <div class='titleContent' style='text-align: center;''>
                <div class='title' style='text-align: start;'>
                    <p style='padding-left: 15px;'> Oi $firstName, Anderson Aqui :)</p>
                    <p style='padding-left: 15px;'> Obrigado por renovar sua inscrição no <b>Método AB</b> ou <b>MAB</b></p>
                    <p style='padding-left: 15px;'> A <b>sua assinatura $subscription foi renovada</b>. Seu plano durará por $duration e o seu contrato já está em anexo.</p>
                    <p style='padding-left: 15px;'> A administradora já está analisando os dados de pagamento e assim que estiver tudo pronto, a gente te avisa!</p>
                    <p style='padding-left: 15px;'> Tchau,</p>
                    <p style='padding-left: 15px;'> Anderson Brandão</p>
                </div>
            </div>
        </div>
    </div>
    </div>
        ";
		
				$mail->isHTML(true);
				$mail->Body= $arquivo;
		
		
				if(!$mail->send()) {
					$mgm = "ERRO AO ENVIAR E-MAIL! " . $mail->ErrorInfo;
					echo "Falha ao enviar o email!";
					echo $mail -> ErrorInfo;
				} else {
					$mgm = "E-MAIL ENVIADO COM SUCESSO! Responderemos em breve.";
					echo "Email enviado com sucesso! inscrição renovada1";
          // ALTERA  O PARÂMETRO DE EMAILRENOVACAO ENVIADO PARA TRUE
          $paramMetadataRenovacao = [
            'metadata' => array(
              'emailEnviado'=> 'false',
              'emailRenovacaoEnviado'=> 'true'
          )
        ];
          
          $atualizaCliente = $vindiService->atualizaCliente($id, $paramMetadataRenovacao);
				}
      }else{
        echo('email enviado igual a true');
      }
		
          echo('já existe usuário na vg');
      
    }else{

      // FAZ UMA BUSCA PARA VERIFICAR O PARÂMETRO DE EMAIL ENVIADO

      $paramBuscaId1 = [
        'query'    => 'id = "'. $id .'"' 
    ];
      $buscaCliente1 = $vindiService->buscarClientes($paramBuscaId1);
      $phoneNumber = $buscaCliente1[0]->phones[0]->number;
      $emailRenovacaoEnviado1 = $buscaCliente1[0]->metadata->emailRenovacaoEnviado;


      if($emailRenovacaoEnviado1 == 'false'){


        $api_wpp = array(
          "number" => $phoneNumber . "@c.us",
          "bodyMessage" => "Olá, Anderson aqui! Estou vindo dizer que sua inscrição ja foi criada, baixe nosso app e ja comece a usar. Te espero no portal :)"
        );
        $data_string = json_encode($api_wpp);
      
      $url = 'https://apiwpp.herokuapp.com/send-message';
      $ch = curl_init($url);
      curl_setopt_array(
          $ch,
          array(
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => $data_string,
              CURLOPT_RETURNTRANSFER => true
          )
      );
      $result = curl_exec($ch);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      
      $response = json_decode($result);

      require_once './email/phpmailer/class.phpmailer.php';
      $mail = new PHPMailer();
      $mail->isSMTP();

      // Configurações do servidor de email
      $mail->Host = $iniMail['HOST'];
      $mail->SMTPAuth = true;
      $mail->SMTPSecure = 'tls';
      $mail->Username = $iniMail['USERNAME'];
      $mail->Password = $iniMail['PASSWD'];//'testecheckout5@gmail.com'//$ini['passwd'];
      $mail->Port = $iniMail['PORT'];

      
      //Configuração da Mensagem
      $emailenviar = $iniMail['USER']; // de onde vai enviar contato@andersonbrandao.com.br
      $destino = $email;   //email da pessoa que vai receber
      $mail->setFrom($emailenviar); //Remetente
      $mail->addAddress($destino); //Destinatário
      $mail->addAttachment('./files/Contrato.pdf');
      $mail->Subject = '=?UTF-8?B?'.base64_encode("Anderson Brandão - Assinatura registrada").'?='; //Assunto do e-mail
      //$mail->addBCC('anderson@andersonbrandao.com.br', 'Coach Anderson');

      //$assunto = "Novo e-mail inscrito - Site Coach Anderson"; //assunto do email
      //$assunto = '=?UTF-8?B?'.base64_encode($assunto).'?=';
      
      $arquivo = "
      <div>
      <div class='container'>
      <div class='content' style='background-color: #FFFFFF;'>
          <div class='header' style='align-items: center; justify-content: center; text-align: center;'>
          <img src='https://www.andersonbrandao.com.br/img/Novo/imgEmail.jpg' alt='Metodo AB>
          </div>
          <div class='titleContent' style='text-align: center;''>
              <div class='title' style='text-align: start;'>
                  <p style='padding-left: 15px;'> Oi $firstName, Anderson Aqui :)</p>
                  <p style='padding-left: 15px;'> Obrigado por se inscrever no <b>Método AB</b> ou <b>MAB</b></p>
                  <p style='padding-left: 15px;'> O primeiro passo foi dado e <b>sua assinatura $subscription foi registrada</b>. Seu plano durará por $duration e o seu contrato já está em anexo.</p>
                  <p style='padding-left: 15px;'> Agora você está a dois passos de se tornar mais saudável e emagrecer usando minha tecnologia exclusiva. A administradora já está analisando os dados de pagamento e assim que estiver tudo pronto, a gente te avisa!</p>
                  <p style='padding-left: 15px;'> Tchau,</p>
                  <p style='padding-left: 15px;'> Anderson Brandão</p>
              </div>
          </div>
      </div>
  </div>
  </div>
      ";
  
      $mail->isHTML(true);
      $mail->Body= $arquivo;
  
  
      if(!$mail->send()) {
        $mgm = "ERRO AO ENVIAR E-MAIL! " . $mail->ErrorInfo;
        echo "Falha ao enviar o email!";
        echo $mail -> ErrorInfo;
      } else {
        $mgm = "E-MAIL ENVIADO COM SUCESSO! Responderemos em breve.";
        echo "Email enviado com sucesso! Nova assinatura1";
        // ATUALIZA O PARÂMETRO DE ENVIO DE EMAILRENOVACAO ENVIADO PARA TRUE.
        $paramMetadataRenovacao = [
          'metadata' => array(
              'emailEnviado'=> 'false',
            'emailRenovacaoEnviado'=> 'true'
        )
      ];
        
        $atualizaCliente = $vindiService->atualizaCliente($id, $paramMetadataRenovacao);
    }

    // FINALIZA O IF DE VERIFICAÇÃO DE PARÂMETRO.  
      }
  

        if($vg['ATIVA_INTEGRACAO'] == 1){

          // BUSCA O CLIENTE NA VINDI PARA PEGAR AS INFORMAÇÕES E CADASTRAR NA VG.

          $paramsBucaClienteVindi = [
            'query'    => 'id = "'. $id.'"'
          ];
          
          $cliente = $vindiService->buscarClientes($paramsBucaClienteVindi);
          $telefone= $cliente[0]->phones[0]->number;
          $sexo = $cliente[0]->metadata->sexo;
          $aniversário = DateTime::createFromFormat('d/m/Y', $cliente[0]->metadata->dataNascimento)->format("Y-m-d");
          $cep = $cliente[0]->address->zipcode;
          $rua = $cliente[0]->address->street;
          $complemento = $cliente[0]->address->additional_details;
          $cidade = $cliente[0]->address->city;
          $pais = $cliente[0]->address->country;
          $motivo = $cliente[0]->metadata->motivoAssinatura;
          $comoConheceu = $cliente[0]->metadata->comoConheceu;

          // ENVIA A REQUISIÇÃO PARA A VG, CADASTRANDO O USUÁRIO

        $customer_data = array(
          "firstname" => $firstName,
          "lastname" => $lastName,
          "email" => $email,
          "phone"=>$cliente[0]->phones[0]->number,
          "active" => false,
          "is_pro" => true,
          "goal_id" => $cliente[0]->metadata->motivoAssinatura,
          "gender" => $cliente[0]->metadata->sexo,
          "birthday" =>DateTime::createFromFormat('d/m/Y', $cliente[0]->metadata->dataNascimento)->format("Y-m-d"),
          "zip" =>  $cliente[0]->address->zipcode, //str_replace("-", ' ',$cliente[0]->address-zipcode)
          "street" => $cliente[0]->address->street,
          "street_extra" => $cliente[0]->address->additional_details,
          "place" => $cliente[0]->address->city,
          "country" => $cliente[0]->address->country

      );
      $data_string = json_encode($customer_data);
      
      $url = $vg['API_URL'] . 'club/' . $vg['CLUB_ID'] . '/member?api_key=' . $vg['API_KEY'] . '&club_secret=' . $vg['CLUB_KEY'];
      $ch = curl_init($url);
      curl_setopt_array(
          $ch,
          array(
              CURLOPT_CUSTOMREQUEST => 'PUT',
              CURLOPT_POSTFIELDS => $data_string,
              CURLOPT_RETURNTRANSFER => true
          )
      );
      $result = curl_exec($ch);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      
      $response = json_decode($result);

      // SE O STATUS DA REQUISIÇÃO FOR DIFERENTE DE 200

      if($httpcode != 200){

        require_once './email/phpmailer/class.phpmailer.php';
				$mail = new PHPMailer();
				$mail->isSMTP();

				// Configurações do servidor de email
				$mail->Host = $iniMail['HOST'];
				$mail->SMTPAuth = true;
				$mail->SMTPSecure = 'tls';
				$mail->Username = $iniMail['USERNAME'];
				$mail->Password = $iniMail['PASSWD'];//'testecheckout5@gmail.com'//$ini['passwd'];
				$mail->Port = $iniMail['PORT'];

				
				//Configuração da Mensagem
				$emailenviar = $iniMail['USER']; // de onde vai enviar contato@andersonbrandao.com.br
				$destino = $iniMail['EMAIL_SUPORTE'];   //email da pessoa que vai receber
				$mail->setFrom($emailenviar); //Remetente
				$mail->addAddress($destino); //Destinatário
        $mail->addAttachment('contrato.pdf', 'contrato'); //Anexo contrato que será enviado.
				$mail->Subject = '=?UTF-8?B?'.base64_encode("Anderson Brandão - CODE 11. Erro ao cadastrar usuário.").'?='; //Assunto do e-mail
				//$mail->addBCC('anderson@andersonbrandao.com.br', 'Coach Anderson');

				//$assunto = "Novo e-mail inscrito - Site Coach Anderson"; //assunto do email
				//$assunto = '=?UTF-8?B?'.base64_encode($assunto).'?=';
				
				$arquivo = " 
        CODE: 11
        <p>Houve um erro ao cadastrar o usuário $id na VG, a requisição não foi processada com sucesso.</p>
        <p>Dados do usuário:</p>
        <p>Nome: $fullName</p>
        <p>Email: $email</p>
        <p>CPF: $code</p>
        <p>ID: $id</p>
        ";
		
				$mail->isHTML(true);
				$mail->Body= $arquivo;
		
		
				if(!$mail->send()) {
					$mgm = "ERRO AO ENVIAR E-MAIL! " . $mail->ErrorInfo;
					echo "Falha ao enviar o email!";
					echo $mail -> ErrorInfo;
				} else {
					$mgm = "E-MAIL ENVIADO COM SUCESSO! Responderemos em breve.";
					echo "Email enviado com sucesso!"; 
				}
      }else{

        // SE O STATUS DA REQUISIÇÃO FOR IGUAL A 200, ATUALIZA NA VINDI O CLIENTE, INSERINDO O MEMBER ID
        $member_id = $response->result->member_id;
       

        $paramMetadata = [
          'metadata' => array(
          'memberId' => $member_id,
          'emailEnviado'=> 'false'
        )
      ];
    
    $atualizaCliente = $vindiService->atualizaCliente($id, $paramMetadata);

      //VERIFICA SE O PARÂMETRO MEMBERID DO CLIENTE NA VINDI ESTÁ VAZIO.

        //ENVIA REQUISIÇÃO ATIVANDO O USUÁRIO NA VG.
        $customer_data = array(
          "active" => true,
      );
      $data_string = json_encode($customer_data);
      
      $url = $vg['API_URL'] . 'club/' . $vg['CLUB_ID'] .'/'.'member/'. $member_id .'?api_key=' . $vg['API_KEY'] . '&club_secret=' . $vg['CLUB_KEY'];
      $ch = curl_init($url);
      curl_setopt_array(
          $ch,
          array(
              CURLOPT_CUSTOMREQUEST => 'PUT',
              CURLOPT_POSTFIELDS => $data_string,
              CURLOPT_RETURNTRANSFER => true
          )
      );
      $result = curl_exec($ch);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

    //VERIFICA SE O MEMBER ID QUE FOI INSERIDO É IGUAL AO CRIADO
    
    if($atualizaCliente->metadata->memberId != $member_id){

      // CASO SEJA DIFERENTE, ENVIAR UM EMAIL RELATANDO O ERRO.

      require_once './email/phpmailer/class.phpmailer.php';
      $mail = new PHPMailer();
      $mail->isSMTP();

      // Configurações do servidor de email
      $mail->Host = $iniMail['HOST'];
      $mail->SMTPAuth = true;
      $mail->SMTPSecure = 'tls';
      $mail->Username = $iniMail['USERNAME'];
      $mail->Password = $iniMail['PASSWD'];//'testecheckout5@gmail.com'//$ini['passwd'];
      $mail->Port = $iniMail['PORT'];

      
      //Configuração da Mensagem
      $emailenviar = $iniMail['USER']; // de onde vai enviar contato@andersonbrandao.com.br
      $destino = $iniMail['EMAIL_SUPORTE'];   //email da pessoa que vai receber
      $mail->setFrom($emailenviar); //Remetente
      $mail->addAddress($destino); //Destinatário
      $mail->addAttachment('contrato.pdf', 'contrato'); //Anexo contrato que será enviado.
      $mail->Subject = '=?UTF-8?B?'.base64_encode("Anderson Brandão - CODE 12. Erro ao inserir Member id.").'?='; //Assunto do e-mail
      //$mail->addBCC('anderson@andersonbrandao.com.br', 'Coach Anderson');

      //$assunto = "Novo e-mail inscrito - Site Coach Anderson"; //assunto do email
      //$assunto = '=?UTF-8?B?'.base64_encode($assunto).'?=';
      
      $arquivo = " 
      CODE: 12
      <p>Houve um erro ao inserir o member id, do usuário $id, na Vindi. A requisição não foi processada com sucesso.</p>

      <p>Dados do usuário:</p>

      <p>Nome: $fullName</p>
      <p>Email: $email</p>
      <p>CPF: $code</p>
      <p>ID: $id</p>
      ";

  
      $mail->isHTML(true);
      $mail->Body= $arquivo;
  
  
      if(!$mail->send()) {
        $mgm = "ERRO AO ENVIAR E-MAIL! " . $mail->ErrorInfo;
        echo "Falha ao enviar o email!";
        echo $mail -> ErrorInfo;
      } else {
        $mgm = "E-MAIL ENVIADO COM SUCESSO! Responderemos em breve.";
        echo "Email enviado com sucesso!";
      }
    }
      
      }
      
    } else {
      echo('Ativação na VG desabilitada');

      $paramsBucaClienteVindi = [
        'query'    => 'id = "'. $id.'"'
      ];
      
      $cliente = $vindiService->buscarClientes($paramsBucaClienteVindi);

      $telefone= $cliente[0]->phones[0]->number;
          $sexo = $cliente[0]->metadata->sexo;
          $aniversario = DateTime::createFromFormat('d/m/Y', $cliente[0]->metadata->dataNascimento)->format("Y-m-d");
          $cep = $cliente[0]->address->zipcode;
          $rua = $cliente[0]->address->street;
          $complemento = $cliente[0]->address->additional_details;
          $cidade = $cliente[0]->address->city;
          $pais = $cliente[0]->address->country;
          $motivo = $cliente[0]->metadata->motivoAssinatura;
          $comoConheceu = $cliente[0]->metadata->comoConheceu;
      
      require_once './email/phpmailer/class.phpmailer.php';
      $mail = new PHPMailer();
      $mail->isSMTP();

      // Configurações do servidor de email
      $mail->Host = $iniMail['HOST'];
      $mail->SMTPAuth = true;
      $mail->SMTPSecure = 'tls';
      $mail->Username = $iniMail['USERNAME'];
      $mail->Password = $iniMail['PASSWD'];//'testecheckout5@gmail.com'//$ini['passwd'];
      $mail->Port = $iniMail['PORT'];

      
      //Configuração da Mensagem
      $emailenviar = $iniMail['USER']; // de onde vai enviar contato@andersonbrandao.com.br
      $destino = $iniMail['EMAIL_SUPORTE'];   //email da pessoa que vai receber
      $mail->setFrom($emailenviar); //Remetente
      $mail->addAddress($destino); //Destinatário
      $mail->Subject = '=?UTF-8?B?'.base64_encode("Anderson Brandão - Novo usuário para ser cadastrado").'?='; //Assunto do e-mail
      //$mail->addBCC('anderson@andersonbrandao.com.br', 'Coach Anderson');

      //$assunto = "Novo e-mail inscrito - Site Coach Anderson"; //assunto do email
      //$assunto = '=?UTF-8?B?'.base64_encode($assunto).'?=';
      
      $arquivo = "
      <div>
      <div class='container'>
      <div class='content' style='background-color: #FFFFFF;'>
          <div class='header' style='align-items: center; justify-content: center; text-align: center;'>
          <img src='https://www.andersonbrandao.com.br/img/Novo/imgEmail.jpg' alt='Metodo AB>
          </div>
          <div class='titleContent' style='text-align: center;''>
              <div class='title' style='text-align: start;'>

                  <p style='padding-left: 15px;'> DADOS DO USUÁRIO </p>

                  <p style='padding-left: 15px;'> 'nome'=> $firstName,</p>
                  <p style='padding-left: 15px;'>'sobrenome' => $lastName,</p>
                  <p style='padding-left: 15px;'>'email' => $email,</p>
                  <p style='padding-left: 15px;'>'telefone'=>$telefone,</p>
                  <p style='padding-left: 15px;'>'active' => false,</p>
                  <p style='padding-left: 15px;'>'is_pro' => true,</p>
                  <p style='padding-left: 15px;'>'motivo da assinatura' => $motivo,</p>
                  <p style='padding-left: 15px;'>'sexo' => $sexo,</p>
                  <p style='padding-left: 15px;'>'aniversario' =>$aniversario,</p>
                  <p style='padding-left: 15px;'>'cep' =>  $cep,</p>
                  <p style='padding-left: 15px;'>'rua' => $rua,</p>
                  <p style='padding-left: 15px;'>'complemento' => $complemento,</p>
                  <p style='padding-left: 15px;'>'cidade' => $cidade</p>
                  <p style='padding-left: 15px;'>'pais' => $pais</p>
                  <p style='padding-left: 15px;'>'como conheceu' => $comoConheceu</p>
              </div>
          </div>
      </div>
  </div>
  </div>
      ";
  
      $mail->isHTML(true);
      $mail->Body= $arquivo;
  
  
      if(!$mail->send()) {
        $mgm = "ERRO AO ENVIAR E-MAIL! " . $mail->ErrorInfo;
        echo "Falha ao enviar o email!";
        echo $mail -> ErrorInfo;
      } else {
        $mgm = "E-MAIL ENVIADO COM SUCESSO! Responderemos em breve.";
        echo "Email enviado com sucesso! cadastro na vg";
        // ATUALIZA O PARÂMETRO DE ENVIO DE EMAILRENOVACAO ENVIADO PARA TRUE.
    }
    }
  }//else {
    //echo('Já existe um usuário na vg');
  //}
    //echo('inscrição efetuada');
    
        break;

    case 'charge_rejected':

      $arquivo = file_get_contents('php://input');
      $json = json_decode($arquivo);
      $email = $json->event->data->charge->customer->email;
      $fullName = $json->event->data->charge->customer->name;
      $idCustumer = $json->event->data->charge->customer->id;
      $code = $json->event->data->charge->customer->code;
      $message = $json->event->data->charge->last_transaction->gateway_message;
      $price = $json->event->data->charge->amount;
      $name = (explode(" ", $fullName));
      $firstName = array_shift($name);
      $priceFormated = number_format((float)$price, 2, '.', '');

      require_once './email/phpmailer/class.phpmailer.php';
      $mail = new PHPMailer();
      $mail->isSMTP();

      // Configurações do servidor de email
      $mail->Host = $iniMail['HOST'];
      $mail->SMTPAuth = true;
      $mail->SMTPSecure = 'tls';
      $mail->Username = $iniMail['USERNAME'];
      $mail->Password = $iniMail['PASSWD'];//'testecheckout5@gmail.com'//$ini['passwd'];
      $mail->Port = $iniMail['PORT'];

      
      //Configuração da Mensagem
      $emailenviar = $iniMail['USER']; // de onde vai enviar contato@andersonbrandao.com.br
      $destino = $email;   //email da pessoa que vai receber
      $mail->setFrom($emailenviar); //Remetente
      $mail->addAddress($destino); //Destinatário
      $mail->Subject = '=?UTF-8?B?'.base64_encode("Anderson Brandão - Cobrança rejeitada").'?='; //Assunto do e-mail
      //$mail->addBCC('anderson@andersonbrandao.com.br', 'Coach Anderson');

      //$assunto = "Novo e-mail inscrito - Site Coach Anderson"; //assunto do email
      //$assunto = '=?UTF-8?B?'.base64_encode($assunto).'?=';
      
      $arquivo = "
      <div>
      <div class='container'>
      <div class='content' style='background-color: #FFFFFF;'>
          <div class='header' style='align-items: center; justify-content: center; text-align: center;'>
          <img src='https://www.andersonbrandao.com.br/img/Novo/imgEmail.jpg' alt='Metodo AB>
          </div>
          <div class='titleContent' style='text-align: center;''>
              <div class='title' style='text-align: start;'>
                  <p style='padding-left: 15px;'> Oi $firstName, Anderson Aqui!</p>
                  <p style='padding-left: 15px;'> Infelizmente, o seu pagamento não foi autorizado. :(</p>
                  <p style='padding-left: 15px;'> Peço que entre em contato com o banco emissor do seu cartão para saber o que aconteceu.</p>
                  <p style='padding-left: 15px;'> Lembrando que a cobrança foi no valor de <b>R$ $priceFormated</b> e que aparecerá no seu cartão como <b>VIRTUAGYM DO BRASIL ATIVIDADES DE CONDICIONAMENTO FISICO EIRELI</b>.</p>
                  <p style='padding-left: 15px;'> Assim que tiver uma solução, me chama no whatsapp para resolvermos esta pendência e evitarmos maiores problemas.</p>
                  <p style='padding-left: 15px;'> Até daqui a pouco,</p>
                  <p style='padding-left: 15px;'> Anderson Brandão</p>
              </div>
          </div>
      </div>
  </div>
  </div>
      ";
  
      $mail->isHTML(true);
      $mail->Body= $arquivo;
  
  
      if(!$mail->send()) {
        $mgm = "ERRO AO ENVIAR E-MAIL! " . $mail->ErrorInfo;
        echo "Falha ao enviar o email!";
        echo $mail -> ErrorInfo;
      } else {
        $mgm = "E-MAIL ENVIADO COM SUCESSO! Responderemos em breve.";
        echo "Email enviado com sucesso!";
      }

      require_once './email/phpmailer/class.phpmailer.php';
      $mail = new PHPMailer();
      $mail->isSMTP();

      // Configurações do servidor de email
      $mail->Host = $iniMail['HOST'];
      $mail->SMTPAuth = true;
      $mail->SMTPSecure = 'tls';
      $mail->Username = $iniMail['USERNAME'];
      $mail->Password = $iniMail['PASSWD'];//'testecheckout5@gmail.com'//$ini['passwd'];
      $mail->Port = $iniMail['PORT'];

      
      //Configuração da Mensagem
      $emailenviar = $iniMail['USER']; // de onde vai enviar contato@andersonbrandao.com.br
      $destino = $iniMail['USER'];   //email da pessoa que vai receber $iniMail['USER']
      $mail->setFrom($emailenviar); //Remetente
      $mail->addAddress($destino); //Destinatário
      $mail->Subject = '=?UTF-8?B?'.base64_encode("Anderson Brandão - Cobrança rejeitada").'?='; //Assunto do e-mail
      //$mail->addBCC('anderson@andersonbrandao.com.br', 'Coach Anderson');

      //$assunto = "Novo e-mail inscrito - Site Coach Anderson"; //assunto do email
      //$assunto = '=?UTF-8?B?'.base64_encode($assunto).'?=';
      
      $arquivo = "
      <div>
      <div class='container'>
      <div class='content' style='background-color: #FFFFFF;'>
          <div class='header' style='align-items: center; justify-content: center; text-align: center;'>
          <img src='https://www.andersonbrandao.com.br/img/Novo/imgEmail.jpg' alt='Metodo AB>
          </div>
          <div class='titleContent' style='text-align: center;''>
              <div class='title' style='text-align: start;'>
                  
                  <p style='padding-left: 15px;'> Houve um caso de cobrança rejeitada, segue os dados do cliente abaixo.</p>
                  <p style='padding-left: 15px;'> Nome: $fullName</p>
                  <p style='padding-left: 15px;'> Cpf: $code</p>
                  <p style='padding-left: 15px;'> Id do cliente: $idCustumer</p>
                  <p style='padding-left: 15px;'> Email: $email</p>
                  <p style='padding-left: 15px;'> Mensagem: $message</p>
              </div>
          </div>
      </div>
  </div>
  </div>
      ";
  
      $mail->isHTML(true);
      $mail->Body= $arquivo;
  
  
      if(!$mail->send()) {
        $mgm = "ERRO AO ENVIAR E-MAIL! " . $mail->ErrorInfo;
        echo "Falha ao enviar o email!";
        echo $mail -> ErrorInfo;
      } else {
        $mgm = "E-MAIL ENVIADO COM SUCESSO! Responderemos em breve.";
        echo "Email enviado com sucesso!";
      }



        echo('cobrança rejeitada');
        break;
    case 'bill_created':
        echo('fatura emitida');
        break;

    case 'bill_paid':
        echo('fatura paga');
        $arquivo = file_get_contents('php://input');
        $json = json_decode($arquivo);

        $fullName = $json->event->data->bill->customer->name;
        $cycle = $json->event->data->bill->period->cycle;
        $email = $json->event->data->bill->customer->email;
        $name = (explode(" ", $fullName));
        $firstName = array_shift($name);

      //ESPERA POR 20 SEGUNDOS PARA EXECUTAR O CÓDIGO ABAIXO, A FIM DE QUE JÁ TENHA DADO TEMPO DE ATUALIZAR O MEMBER ID NA VINDI
      sleep(20);

      // VERIFICA SE É A PRIMEIRA FATURA
      if($cycle == 1) {

      //ENVIA UMA REQUISIÇÃO PARA A VG, VERIFICANDO SE HÁ UM USUÁRIO CADASTRADO ATRAVÉS DO EMAIL
        $customer_data = array(
          "email" => $email
      );
      $data_string = json_encode($customer_data);
      
      $url = $vg['API_URL'] . 'club/' . $vg['CLUB_ID'] . '/member?api_key=' . $vg['API_KEY'] . '&club_secret=' . $vg['CLUB_KEY'] . '&email=' . $email;
      $ch = curl_init($url);
      curl_setopt_array(
          $ch,
          array(
              CURLOPT_CUSTOMREQUEST => 'GET',
              CURLOPT_POSTFIELDS => $data_string,
              CURLOPT_RETURNTRANSFER => true
          )
      );
      $resultado = curl_exec($ch);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      $resposta = json_decode($resultado);

      //VERIFICA SE A QUANTIDADE DE CONTAS NA VG É MAIOR OU IGUAL A 1

      if(count($resposta->result) >= 1){

        $idBusca = $json->event->data->bill->customer->id;
        
        $paramBuscaId = [
          'query'    => 'id = "'. $idBusca .'"' 
      ];
        $buscaCliente = $vindiService->buscarClientes($paramBuscaId);
        $emailEnviado = $buscaCliente[0]->metadata->emailEnviado;

        if($emailEnviado == 'false'){

        require_once './email/phpmailer/class.phpmailer.php';
        $mail = new PHPMailer();
        $mail->isSMTP();
  
        // Configurações do servidor de email
        $mail->Host = $iniMail['HOST'];
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';
        $mail->Username = $iniMail['USERNAME'];
        $mail->Password = $iniMail['PASSWD'];//'testecheckout5@gmail.com'//$ini['passwd'];
        $mail->Port = $iniMail['PORT'];
  
        
        //Configuração da Mensagem
        $emailenviar = $iniMail['USER']; // de onde vai enviar contato@andersonbrandao.com.br
        $destino = $email;   //email da pessoa que vai receber
        $mail->setFrom($emailenviar); //Remetente
        $mail->addAddress($destino); //Destinatário
        $mail->Subject = '=?UTF-8?B?'.base64_encode("Anderson Brandão - Pagamento efetuado").'?='; //Assunto do e-mail
        //$mail->addBCC('anderson@andersonbrandao.com.br', 'Coach Anderson');
  
        //$assunto = "Novo e-mail inscrito - Site Coach Anderson"; //assunto do email
        //$assunto = '=?UTF-8?B?'.base64_encode($assunto).'?=';
        
        $arquivo = "
        <div>
        <div class='container'>
        <div class='content' style='background-color: #FFFFFF;'>
            <div class='header' style='align-items: center; justify-content: center; text-align: center;'>
            <img src='https://www.andersonbrandao.com.br/img/Novo/imgEmail.jpg' alt='Metodo AB>
            </div>
            <div class='titleContent' style='text-align: center;''>
                <div class='title' style='text-align: start;'>
                    <p style='padding-left: 15px;'> Oi $firstName, Anderson Aqui  de novo :)</p>
                    <p style='padding-left: 15px;'> Está tudo certo com o seu pagamento! <b>Método AB</b> ou <b>MAB</b></p>
                    <p style='padding-left: 15px;'> Como você já conhece o <b>Portal AB Saúde</b>, te ecnontro lá.</p>
                    <p style='padding-left: 15px;'> A administradora já está analisando os dados de pagamento e assim que estiver tudo pronto, a gente te avisa!</p>
                    <p style='padding-left: 15px;'> Até daqui a pouco,</p>
                    <p style='padding-left: 15px;'> Anderson Brandão</p>
                </div>
            </div>
        </div>
    </div>
    </div>
        ";
    
        $mail->isHTML(true);
        $mail->Body= $arquivo;
    
    
        if(!$mail->send()) {
          $mgm = "ERRO AO ENVIAR E-MAIL! " . $mail->ErrorInfo;
          echo "Falha ao enviar o email!";
          echo $mail -> ErrorInfo;
        } else {
          $mgm = "E-MAIL ENVIADO COM SUCESSO! Responderemos em breve.";
          echo "Email enviado com sucesso!";
          $paramMetadata = [
            'metadata' => array(
              'emailEnviado'=> 'true'
          )
        ];
          
          $atualizaCliente = $vindiService->atualizaCliente($idBusca, $paramMetadata);
				}
        }else{
          echo 'Um email já foi enviado para esse usuário!';
        }

      }else{ // o numero de contas é maior que um.

        $idBusca = $json->event->data->bill->customer->id;
        
          $paramBuscaId = [
            'query'    => 'id = "'. $idBusca .'"' 
        ];
          $buscaCliente2 = $vindiService->buscarClientes($paramBuscaId);
          $emailEnviado = $buscaCliente2[0]->metadata->emailEnviado;

          //$emailInativo = substr($notes, 0, -1);

          //$lastChar = substr($notes, -1);

        // VERIFICA SE O EMAIL FOI ENVIADO AO CLIENTE

        if($emailEnviado == 'false'){
         
        require_once './email/phpmailer/class.phpmailer.php';
				$mail = new PHPMailer();
				$mail->isSMTP();

				// Configurações do servidor de email
				$mail->Host = $iniMail['HOST'];
				$mail->SMTPAuth = true;
				$mail->SMTPSecure = 'tls';
				$mail->Username = $iniMail['USERNAME'];
				$mail->Password = $iniMail['PASSWD'];//'testecheckout5@gmail.com'//$ini['passwd'];
				$mail->Port = $iniMail['PORT'];

				
				//Configuração da Mensagem
				$emailenviar = $iniMail['USER']; // de onde vai enviar contato@andersonbrandao.com.br
				$destino = $email;   //email da pessoa que vai receber
				$mail->setFrom($emailenviar); //Remetente
				$mail->addAddress($destino); //Destinatário
				$mail->Subject = '=?UTF-8?B?'.base64_encode("Anderson Brandão - Pagamento efetuado").'?='; //Assunto do e-mail
				//$mail->addBCC('anderson@andersonbrandao.com.br', 'Coach Anderson');

				//$assunto = "Novo e-mail inscrito - Site Coach Anderson"; //assunto do email
				//$assunto = '=?UTF-8?B?'.base64_encode($assunto).'?=';
				
				$arquivo = "
        <div>
        <div class='container'>
        <div class='content' style='background-color: #FFFFFF;'>
            <div class='header' style='align-items: center; justify-content: center; text-align: center;'>
            <img src='https://www.andersonbrandao.com.br/img/Novo/imgEmail.jpg' alt='Metodo AB>
            </div>
            <div class='titleContent' style='text-align: center;''>
                <div class='title' style='text-align: start;'>
                    <p style='padding-left: 15px;'> Oi $firstName, Anderson Aqui de novo :)</p>
                    <p style='padding-left: 15px;'> Está tudo certo com o seu pagamento! </p>
                    <p style='padding-left: 15px;'> <b>O segundo passo já passou</b> e o pagamento foi aprovado!</p>
                    <p style='padding-left: 15px;'> Agora a gente já está cuidando de tudo para montar a sua conta no Portal <b>AB Saúde</b>. Daqui a pouquinho, você receberá o e-mail para a ativação da sua conta e cadastramento de senha.</p>
                    <p style='padding-left: 15px;'> Se quiser se adiantar, já entre lá na loja de aplicativos do seu celular e baixe o meu app <b>AB Saúde</b>.</p>
                    <p style='padding-left: 15px;'> Até daqui a pouco,</p>
                    <p style='padding-left: 15px;'> Anderson Brandão</p>
                </div>
            </div>
        </div>
    </div>
    </div>
        ";
		
				$mail->isHTML(true);
				$mail->Body= $arquivo;
		
		
				if(!$mail->send()) {
					$mgm = "ERRO AO ENVIAR E-MAIL! " . $mail->ErrorInfo;
					echo "Falha ao enviar o email!";
					echo $mail -> ErrorInfo;
				} else {
					$mgm = "E-MAIL ENVIADO COM SUCESSO! Responderemos em breve.";
          echo ('email enviado com sucesso');
          // ATUALIZA O PARÂMETRO DE EMAIL DE PAGAMENTO ENVIADO COMO TRUE.
          $paramMetadata = [
            'metadata' => array(
              'emailEnviado'=> 'true'
          )
        ];
          
          $atualizaCliente = $vindiService->atualizaCliente($idBusca, $paramMetadata);
				} // TERMINA AQUI
      }else{
        echo 'Um email já foi enviado para esse usuário!';
      }
  

        if($vg['ATIVA_INTEGRACAO'] == 1){

         /*         
        // ATIVA USUÁRIO NA VG

      $member_id = $buscaCliente[0]->metadata->memberId;
      $nameC = $buscaCliente[0]->name;
      $emailC = $buscaCliente[0]->email;
      $codeC = $buscaCliente[0]->code;

      //VERIFICA SE O PARÂMETRO MEMBERID DO CLIENTE NA VINDI ESTÁ VAZIO.

      if(!empty($member_id)){
        //ESPERA POR 5 SEGUNDOS PARA EXECUTAR O  CÓDIGO ABAIXO.
        sleep(5);
        //ENVIA REQUISIÇÃO ATIVANDO O USUÁRIO NA VG.
        $customer_data = array(
          "active" => true,
      );
      $data_string = json_encode($customer_data);
      
      $url = $vg['API_URL'] . 'club/' . $vg['CLUB_ID'] .'/'.'member/'. $member_id .'?api_key=' . $vg['API_KEY'] . '&club_secret=' . $vg['CLUB_KEY'];
      $ch = curl_init($url);
      curl_setopt_array(
          $ch,
          array(
              CURLOPT_CUSTOMREQUEST => 'PUT',
              CURLOPT_POSTFIELDS => $data_string,
              CURLOPT_RETURNTRANSFER => true
          )
      );
      $result = curl_exec($ch);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      

      // STATUS DA REQUISIÇÃO DE ATIVAÇÃO RETORNOU ERRO
      if($httpcode != 200){

        require_once './email/phpmailer/class.phpmailer.php';
				$mail = new PHPMailer();
				$mail->isSMTP();

				// Configurações do servidor de email
				$mail->Host = $iniMail['HOST'];
				$mail->SMTPAuth = true;
				$mail->SMTPSecure = 'tls';
				$mail->Username = $iniMail['USERNAME'];
				$mail->Password = $iniMail['PASSWD'];//'testecheckout5@gmail.com'//$ini['passwd'];
				$mail->Port = $iniMail['PORT'];

				
				//Configuração da Mensagem
				$emailenviar = $iniMail['USER']; // de onde vai enviar contato@andersonbrandao.com.br
				$destino = $iniMail['EMAIL_SUPORTE'];   //email da pessoa que vai receber
				$mail->setFrom($emailenviar); //Remetente
				$mail->addAddress($destino); //Destinatário
        $mail->addAttachment('contrato.pdf', 'contrato'); //Anexo contrato que será enviado.
				$mail->Subject = '=?UTF-8?B?'.base64_encode("Anderson Brandão - CODE 13. Erro ao ativar usuário.").'?='; //Assunto do e-mail
				
				
				$arquivo = " 
        CODE: 13
        <p>Erro ao ativar usuário $idBusca na VG, a requisição não foi processada com sucesso.</p>

        <p>Dados do usuário:</p>

        <p>Nome: $nameC</p>
        <p>Email: $emailC</p>
        <p>CPF: $codeC</p>
        <p>ID: $idBusca</p>
        ";
		
				$mail->isHTML(true);
				$mail->Body= $arquivo;
		
		
				if(!$mail->send()) {
					$mgm = "ERRO AO ENVIAR E-MAIL! " . $mail->ErrorInfo;
					echo "Falha ao enviar o email!";
					echo $mail -> ErrorInfo;
				} else {
					$mgm = "E-MAIL ENVIADO COM SUCESSO! Responderemos em breve.";
					echo "Email enviado com sucesso!";
				}
        
      }else {
        // STATUS DA REQUISIÇÃO DE ATIVAÇÃO DEU CERTO
        echo('Ocorreu tudo certo!'); 
      }


    }else {
      // CASO MEMBER_ID ESTEJA VAZIO
      require_once './email/phpmailer/class.phpmailer.php';
				$mail = new PHPMailer();
				$mail->isSMTP();

				// Configurações do servidor de email
				$mail->Host = $iniMail['HOST'];
				$mail->SMTPAuth = true;
				$mail->SMTPSecure = 'tls';
				$mail->Username = $iniMail['USERNAME'];
				$mail->Password = $iniMail['PASSWD'];//'testecheckout5@gmail.com'//$ini['passwd'];
				$mail->Port = $iniMail['PORT'];

				
				//Configuração da Mensagem
				$emailenviar = $iniMail['USER']; // de onde vai enviar contato@andersonbrandao.com.br
				$destino = $iniMail['EMAIL_SUPORTE'];   //email da pessoa que vai receber
				$mail->setFrom($emailenviar); //Remetente
				$mail->addAddress($destino); //Destinatário
        $mail->addAttachment('contrato.pdf', 'contrato'); //Anexo contrato que será enviado.
				$mail->Subject = '=?UTF-8?B?'.base64_encode("Anderson Brandão - CODE 14. Member_id vazio.").'?='; //Assunto do e-mail

				
				$arquivo = " 
        CODE: 14
        <p>Erro ao ativar usuário na VG, member_id vazio.</p>

        <p>Dados do usuário:</p> 

        <p>Nome: $nameC</p>
        <p>Email: $emailC</p>
        <p>CPF: $codeC</p>
        <p>ID: $idBusca</p>
        ";
		
				$mail->isHTML(true);
				$mail->Body= $arquivo;
		
		
				if(!$mail->send()) {
					$mgm = "ERRO AO ENVIAR E-MAIL! " . $mail->ErrorInfo;
					echo "Falha ao enviar o email!";
					echo $mail -> ErrorInfo;
				} else {
					$mgm = "E-MAIL ENVIADO COM SUCESSO! Responderemos em breve.";
					echo "Email enviado com sucesso!";
				}
    }*/

    } else {
      // ATIVAÇÃO DESABILITADA
      echo('Ativação na VG desabilitada');
    }
  }

  }else{

    // NÃO É A PRIMEIRA FATURA, OU SEJA, É UM CASO DE RENOVAÇÃO
      echo('não é a primeira fatura');
      require_once './email/phpmailer/class.phpmailer.php';
      $mail = new PHPMailer();
      $mail->isSMTP();

      // Configurações do servidor de email
      $mail->Host = $iniMail['HOST'];
      $mail->SMTPAuth = true;
      $mail->SMTPSecure = 'tls';
      $mail->Username = $iniMail['USERNAME'];
      $mail->Password = $iniMail['PASSWD'];//'testecheckout5@gmail.com'//$ini['passwd'];
      $mail->Port = $iniMail['PORT'];

      
      //Configuração da Mensagem
      $emailenviar = $iniMail['USER']; // de onde vai enviar contato@andersonbrandao.com.br
      $destino = $email;   //email da pessoa que vai receber
      $mail->setFrom($emailenviar); //Remetente
      $mail->addAddress($destino); //Destinatário
      $mail->Subject = '=?UTF-8?B?'.base64_encode("Anderson Brandão - Pagamento efetuado").'?='; //Assunto do e-mail
      //$mail->addBCC('anderson@andersonbrandao.com.br', 'Coach Anderson');

      //$assunto = "Novo e-mail inscrito - Site Coach Anderson"; //assunto do email
      //$assunto = '=?UTF-8?B?'.base64_encode($assunto).'?=';
      
      $arquivo = "
      <div>
      <div class='container'>
      <div class='content' style='background-color: #FFFFFF;'>
          <div class='header' style='align-items: center; justify-content: center; text-align: center;'>
          <img src='https://www.andersonbrandao.com.br/img/Novo/imgEmail.jpg' alt='Metodo AB>
          </div>
          <div class='titleContent' style='text-align: center;''>
              <div class='title' style='text-align: start;'>
                  <p style='padding-left: 15px;'> Oi $firstName, Anderson Aqui  de novo :)</p>
                  <p style='padding-left: 15px;'> Está tudo certo com o seu pagamento! <b>Método AB</b> ou <b>MAB</b></p>
                  <p style='padding-left: 15px;'> Como você já conhece o <b>Portal AB Saúde</b>, te ecnontro lá.</p>
                  <p style='padding-left: 15px;'> A administradora já está analisando os dados de pagamento e assim que estiver tudo pronto, a gente te avisa!</p>
                  <p style='padding-left: 15px;'> Até daqui a pouco,</p>
                  <p style='padding-left: 15px;'> Anderson Brandão</p>
              </div>
          </div>
      </div>
  </div>
  </div>
      ";
  
      $mail->isHTML(true);
      $mail->Body= $arquivo;
  
  
      if(!$mail->send()) {
        $mgm = "ERRO AO ENVIAR E-MAIL! " . $mail->ErrorInfo;
        echo "Falha ao enviar o email!";
        echo $mail -> ErrorInfo;
      } else {
        $mgm = "E-MAIL ENVIADO COM SUCESSO! Responderemos em breve.";
        echo "Email enviado com sucesso!";
      }
    }
    
        break;
    case 'period_created':
        echo('periodo criado');
        break;
    case 'test':
        echo('teste');
        break;
    default:
        echo('deu erro');
        break;
}


