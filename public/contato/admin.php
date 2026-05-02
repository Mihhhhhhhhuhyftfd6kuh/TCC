<?php
    require '../../controllers/contact.php';


    $usuarios = imprimir_com_conversa();
    


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php foreach($usuarios as $usuarios): ?>
        <?php if($usuarios['id']!=1 ): ?>

        <p><?= htmlspecialchars($usuarios['img']); ?></p>
        <p><?= htmlspecialchars($usuarios['nome']); ?></p>
        <p><?= htmlspecialchars($usuarios['email']); ?></p>


        <a href="conversa.php?id=<?= $usuarios['id']; ?>">
            <button type="button" >Entrar na conversa</button>
        </a>
        <?php endif ?>

    <?php endforeach; ?>
</body>
</html>