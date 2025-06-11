<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
    $password = $_POST['password'];
    // Genera el hash de la contraseña de forma segura
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "<h3>Hash Generado:</h3>";
    echo "<p>Copia esta línea completa y pégala en la constante PASSWORD_HASH de tu script principal:</p>";
    echo "<pre style='background-color:#eee; padding:10px; border:1px solid #ccc; word-wrap:break-word;'>" . htmlspecialchars($hash) . "</pre>";
} else {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador de Hash de Contraseña</title>
    <style>body {font-family: sans-serif; padding: 20px;}</style>
</head>
<body>
    <h2>Generador de Hash para el Visor de Logs</h2>
    <p>Introduce la contraseña que quieres usar para acceder al visor.</p>
    <form method="POST">
        <input type="text" name="password" size="30" placeholder="Escribe tu contraseña aquí">
        <button type="submit">Generar Hash</button>
    </form>
</body>
</html>
<?php
}
?>
