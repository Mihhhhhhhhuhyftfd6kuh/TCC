<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

    <h2>Chat</h2>
    
    <label for="">Nova Mensagem:</label>
    <input type="text" name="mensagem" id="mensagem" placeholder="digite a mensagem"> <br> <br>

    <input type="button" value="Enviar" onclick="enviar()">

    <span id="mensagem-chat"></span>

    <script>

        const mensagemChat = document.getElementById("mensagem-chat");

       const ws = new WebSocket ('ws://localhost:8080');

       ws.onopen = (e) => {
        console.log("conectado");
       }

       ws.onmessage = (mensagemRecebida) => {
        try {
            let resultado = JSON.parse(mensagemRecebida.data);
            mensagemChat.insertAdjacentHTML('beforeend', `${resultado.mensagem} <br>`);
        } catch (e) {
            // se a mensagem não for JSON, exibe o payload bruto
            mensagemChat.insertAdjacentHTML('beforeend', `${mensagemRecebida.data} <br>`);
        }
       }

       const enviar = () => {
            const mensagemInput = document.getElementById("mensagem");
            const dados = { mensagem: mensagemInput.value };

            ws.send(JSON.stringify(dados));

            // mostra imediatamente a mensagem enviada no chat
            mensagemChat.insertAdjacentHTML('beforeend', `${dados.mensagem} <br>`);

            mensagemInput.value = '';
       }
    </script>
    
</body>
</html>