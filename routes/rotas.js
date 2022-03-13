const express = require('express');
const venom = require('venom-bot');
const router = express.Router();

// ROTA PARA O ENVIO DE MENSAGEM

router.post('/send-message', async (req, res) =>{ //@c.us
      
    var number = req.body.number;
    var bodyMessage = req.body.bodyMessage
    //client.addParticipant('Ip1tEmCSALt31cUHQhVic7@g.us', number);
      

      client.sendText(number, bodyMessage).then(response=> {
          res.status(200).json({
          status: true,
          message: 'mensagem enviada',
          response: 'funcionou'
          })
        })
        .catch(error=>{
          console.log(`Esse e o erro: ${error}`);
          console.log(req.body);
        })
     
  })

  // ROTA PARA O ENVIO DE BOTÕES

  router.post('/send-buttons', async (req, res) =>{ //@c.us
      
      
    const buttons = [
        {
          "buttonText": {
            "displayText": "Muito boa!"
            }
          },
        {
          "buttonText": {
            "displayText": "Boa!"
            }
          },
        {
          "buttonText": {
            "displayText": "Mediana"
            }
          },
        {
          "buttonText": {
            "displayText": "Ruim :("
            }
          }
        ]
      await client.sendButtons('5521964159100@c.us', 'Como está sendo a sua experiência com a plataforma ?', buttons, ' Dê a sua avaliação.')
        .then((result) => {
          console.log('Result: ', result); //return object success
        })
        .catch((erro) => {
          console.error('Error when sending: ', erro); //return object error
        });
    })

    // ROTA PARA A CRIAÇÃO DE GRUPO

  router.post('/create-group', async (req, res) =>{ //@c.us
      
      
    var number = req.body.number;
    var bodyMessage = req.body.bodyMessage
    //client.addParticipant('Ip1tEmCSALt31cUHQhVic7@g.us', number);
      

      client.createGroup('Teste VenomApi', [
        "5521964183013@c.us",
        number
      ]).then(res=>{
        res.status.json({
            status: true,
            message: 'mensagem enviada'
        })
        console.log('funcionou');
      })
  })

  router.get('/teste', (req, res) =>{
      res.send('teste');
  })

  module.exports = router;

