const express = require('express');
const path = require('path');
const venom = require('venom-bot');
const app = express();
const server = require('http').createServer(app);
const io = require('socket.io')(server, {cors: {origin: "*"}});
const axios = require("axios");
const bodyParser = require('body-parser');
const rotas = require('./routes/rotas');
const suportMessage = require('./messages.json')
/*app.use(function(req, res, next) {
    res.header("Access-Control-Allow-Origin", "*");
    res.header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept");
    next();
});*/


app.set('view engine', 'ejs');

app.set('views', path.join(__dirname, '/public/views'));

app.get('/home', (req, res)=> {
    //res.send('home'); //'home'
    res.render('home')
})

//app.unsubscribe(express.static(__dirname + '/images'));
var dir = path.join(__dirname, 'public');

app.use(express.static(dir));

//teste para commit

var port = process.env.PORT || 3001

server.listen(port, () => {
  console.log(`listening on port ${port}`)
})


io.on('connection', (socket)=>{

  console.log('user connected:' + socket.id);

  socket.on("message", ()=> {
      venom
.create(
  'sessionName',
  (base64Qr, asciiQR, attempts, urlCode) => {
    console.log(asciiQR); // Optional to log the QR in the terminal
    var matches = base64Qr.match(/^data:([A-Za-z-+\/]+);base64,(.+)$/),
      response = {};

    if (matches.length !== 3) {
      return new Error('Invalid input string');
    }
    response.type = matches[1];
    response.data = new Buffer.from(matches[2], 'base64');

    
    var imageBuffer = response;
    
    axios.post("https://www.andersonbrandao.com.br/criaImagem.php", {code: imageBuffer['data'].toString('base64')}) .then(function(resposta){
      console.log(resposta.data);
    })

    console.log(imageBuffer['data'].toString('base64'));

    require('fs').writeFile(
      './images/out.png', //./images/out.png
      imageBuffer['data'],
      'binary',
      function (err) {
        if (err != null) {
          console.log(err);
        }
      }
    );
  },
  undefined,
  { logQR: false }
)
.then((client) => {start(client);})
.catch((erro) => console.log(erro));

function start (client) {
    client.onStateChange((state) => {
      console.log('usuário conectou');
        socket.emit('message', 'status: ' + state);
        console.log('state changed:', state);
      });
      
      app.use(bodyParser.urlencoded({extended: true}));
      app.use(bodyParser.json());

      const firstSuportMessage = " Por favor, selecione um número: 1 - Não cosigo acessar a página para finalizar compra  2 - Problema com o site  3 - estou em dúvida na hora da compra" 


      client.onMessage((msg) => {
        const user = `${msg.from.replace(/\D/g, '')}@c.us`;
        if(msg.body === "Olá") {
          client.sendText(user, firstSuportMessage).then(response=> {
            res.status(200).json({
            status: true,
            message: 'mensagem enviada',
            response: 'funcionou'
            })
          })
          .catch(error=>{
            console.log(JSON.stringify(error));
          })
        }

        switch(msg.body) {
          case "1" :
            client.sendText(user, suportMessage[0].message);
            break;
          case "2":
            client.sendText(user, suportMessage[1].message);
            break;
          case "3":
            client.sendText(user, suportMessage[2].message);
            break;
        }
      })
      
      //Rota para o envio de mensagem

      app.post('/send-message', async (req, res) =>{ //@c.us
      
        var number = `${req.body.number}@c.us`;
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
              console.log(JSON.stringify(error));
              console.log(req.body);
            })
         
      })
    
      // ROTA PARA O ENVIO DE BOTÕES
    
      app.post('/send-buttons', async (req, res) =>{ //@c.us
          
          
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
    
      app.post('/create-group', async (req, res) =>{ //@c.us
          
          
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
}
  });
  socket.on('ready', () => {
    setTimeout(function (){
          socket.emit('ready', './images/out.png'); //https://www.andersonbrandao.com.br/images/out.png
      }, 3000)
  });
})


//Rotas
/*venom
    .create({
        session: 'session-name', //name of session
        multidevice: false // for version not multidevice use false.(default: true)
      })
      .then((client) => start(client))
      .catch((erro) => {
        console.log(erro);
      });
      function start(client){
          app.use(express.urlencoded());
          app.use(express.json());
          app.post('/send-message', async (req, res) =>{ //@c.us
            console.log(req);
              var number = req.body.number;
              //var number2 = req.body.numberArray2;
              //console.log(req.body.numberArray1);
              var bodyMessage = req.body.bodyMessage
              client.sendText(number, bodyMessage).then(response=> {
                  res.status(200).json({
                  status: true,
                  message: 'mensagem enviada',
                  response: err.text
                  })
              })
              //client.createGroup('Teste VenomApi', array).then(res=>{
                //res.status.json({
                    //status: true,
                    //message: 'mensagem enviada'
                //})
                console.log('funcionou');
              //})
          })
      }*/

app.get('/', (request, response) => {
    console.log('servidor foi chamado');

    return response.send('hello world');
});



app.get('/users', (request, response)=>{
    console.log('servidor conectado');

    return response.send('Página do usuário');
})

app.get('/index', (request, response)=>{
    //console.log('servidor conectado');

    return response.render('index');
})