const express = require('express');
const venom = require('venom-bot');
const app = express();
const server = require('http').createServer(app);
const io = require('socket.io')(server, {cors: {origin: "*"}});

app.set('view engine', 'ejs');

app.get('/home', (req, res)=> {
    res.render('home');
})

app.unsubscribe(express.static(__dirname + '/images'));

server.listen(3001, () => {
    console.log('listening on port 3001')
})

io.on('connection', (socket)=>{
    console.log('user connected:' + socket.io);
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
      require('fs').writeFile(
        './images/out.png',
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
          socket.emit('message', 'status: ' + state);
          console.log('state changed:', state);
      });
  }
    });
    socket.on('ready', () => {
        setTimeout(function (){
            socket.emit('ready', 'htpp://localhost:5500/images/out.png');
        }, 3000)
    });
})