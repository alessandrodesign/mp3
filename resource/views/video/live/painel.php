<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
<h2>Gerenciar Stream Keys</h2>
<table border="1">
    <tr><th>Usuário</th><th>Chave</th><th>Status</th><th>Ações</th></tr>
    <?php foreach ($chaves as $c): ?>
        <tr>
            <td><?= $c['user'] ?></td>
            <td><?= $c['stream_key'] ?></td>
            <td><?= $c['active'] ? 'Ativo' : 'Inativo' ?></td>
            <td>
                <form method="post" action="/video/live/revogar">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button>Revogar</button>
                </form>
            </td>
        </tr>
    <?php endforeach ?>
</table>
</body>
</html>