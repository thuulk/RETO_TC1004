<?php
session_start();

// ===========================================
// Capturar warnings y errores
// ===========================================
$warnings = [];
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$warnings) {
    $warnings[] = "Warning: $errstr in $errfile on line $errline";
    return true; // evita que PHP lo muestre normalmente
});

// ===========================================
// Intento de conexión (puede fallar)
// ===========================================
include "conexion.php";

// ===========================================
// Validación de sesión
// ===========================================
if (!isset($_SESSION["username"])) {
    header("Location: login.html");
    exit();
}

// ===========================================
// Consulta
// ===========================================
$registros = [];
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["fecha"])) {

    $fecha = $_POST["fecha"];
    $hora_inicio = $_POST["hora_inicio"];
    $hora_fin = $_POST["hora_fin"];

    $sql = "SELECT * FROM datosgenerales
            WHERE fecha = ?
            AND hora BETWEEN ? AND ?
            ORDER BY hora ASC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $fecha, $hora_inicio, $hora_fin);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $registros[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historial</title>
  <link rel="stylesheet" href="style.css">

  <style>
    /* ======================= MODALES IGUALES AL DASHBOARD ======================= */
    .alert-box { display: none; position: fixed; inset: 0; backdrop-filter: blur(8px); background: rgba(0,0,0,0.45); justify-content: center; align-items: center; z-index: 9999; animation: fadeIn .25s ease-out; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .alert-content { background: #ffffffee; padding: 35px; border-radius: 22px; width: 420px; max-width: 90%; text-align: center; color: #000; box-shadow: 0 12px 40px rgba(0,0,0,0.25); animation: pop .28s ease-out; }
    @keyframes pop { from { transform: scale(.75); opacity:0; } to { transform: scale(1); opacity:1; } }
    .alert-content input, .alert-content select, .alert-content label { color: #000; font-size: 1.5rem; width: 92%; margin: auto; display: block; text-align: left; }
    .alert-content input, .alert-content select { padding: 12px; border-radius: 10px; border: 1px solid #b7b7b7; margin-bottom: 15px; background: white; }
    .modal-buttons { display: flex; flex-direction: column; gap: 12px; }
    .modal-buttons button { padding: 12px; border-radius: 12px; border: none; font-size: 1.6rem; cursor: pointer; transition: .2s; background: var(--azul); color: white; }
    .modal-buttons button:hover { background: #147a94; transform: scale(1.03); }
    .modal-buttons .cancel { background: #888; }
    .modal-buttons .cancel:hover { background: #666; }

    /* ======================= TABLA ======================= */
    .tabla-container { width: 95%; margin: 30px auto; background: white; padding: 25px; border-radius: 18px; box-shadow: 0 10px 35px rgba(0,0,0,0.15); }
    table { width: 100%; border-collapse: collapse; font-size: 1.5rem; }
    th { background: var(--azul); color: white; padding: 12px; font-size: 1.6rem; }
    td { padding: 10px; border-bottom: 1px solid #ddd; text-align: center; }
    tr:hover { background: #f3faff; }
    .btn-consultar { display: block; padding: 14px 25px; background: var(--naranja); color: white; margin: 20px auto; font-size: 1.6rem; border-radius: 14px; border: none; cursor: pointer; transition: .2s; max-width: 40rem;}
    .btn-consultar:hover { background: #FB8500; transform: scale(1.03); }

    /* ======================= WARNINGS ABAJO ======================= */
    .warnings-container { position: fixed; bottom: 20px; width: 95%; left: 50%; transform: translateX(-50%); z-index: 9999; }
    .warning-box { background: #f8d7da; color: #721c24; padding: 12px 20px; margin-bottom: 8px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); font-size: 1.4rem; }
  </style>
</head>
<body class="dashboard-page">

<header>
   <img src="img/BioAirSolutionsLogo.png" class="logo">
</header>

<nav>
  <div class="nav-links">
    <a href="index.php"><p>Inicio</p></a>
    <a href="historial.php"><p class="activo">Historial</p></a>
  </div>

  <div class="nav-right">
      <button class="nav-btn" onclick="abrirRegistro()">Registrar empleado</button>
      <a href="logout.php"><button class="logout-btn">Cerrar sesión</button></a>
  </div>
</nav>

<div class="container">
    <h2 style="text-align:center; margin-top:20px;">Historial de Registros</h2>
    <button class="btn-consultar" onclick="abrirConsulta()">Consultar registros</button>

    <div class="tabla-container">
        <table>
            <tr>
                <th>Fecha</th><th>Hora</th><th>Humedad</th><th>Temperatura</th><th>Calidad Aire</th><th>Presión</th>
            </tr>

            <?php if ($_SERVER["REQUEST_METHOD"] === "POST"): ?>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="6">No hay resultados en ese rango.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $row): ?>
                        <tr>
                            <td><?= $row["fecha"] ?></td>
                            <td><?= $row["hora"] ?></td>
                            <td><?= $row["humedad"] ?></td>
                            <td><?= $row["temperatura"] ?></td>
                            <td><?= $row["calidad_Aire"] ?></td>
                            <td><?= $row["presion"] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- ======================= MODALES ======================= -->
<div id="consultaBox" class="alert-box">
  <div class="alert-content">
    <h3>Consultar registros</h3>
    <form method="POST" action="historial.php">
        <label>Fecha:</label>
        <input type="date" name="fecha" required>
        <label>Hora inicial:</label>
        <select name="hora_inicio" required>
            <option value="">Selecciona</option>
            <?php for ($h=0;$h<24;$h++): $ho = str_pad($h,2,"0",STR_PAD_LEFT).":00:00"; ?>
            <option value="<?= $ho ?>"><?= $ho ?></option>
            <?php endfor; ?>
        </select>
        <label>Hora final:</label>
        <select name="hora_fin" required>
            <option value="">Selecciona</option>
            <?php for ($h=0;$h<24;$h++): $ho = str_pad($h,2,"0",STR_PAD_LEFT).":00:00"; ?>
            <option value="<?= $ho ?>"><?= $ho ?></option>
            <?php endfor; ?>
        </select>

        <div class="modal-buttons">
          <button type="submit">Consultar</button>
          <button type="button" class="cancel" onclick="cerrarConsulta()">Cancelar</button>
        </div>
    </form>
  </div>
</div>

<div id="registroBox" class="alert-box">
  <div class="alert-content">
    <h3>Registrar empleado</h3>
    <form action="record.php" method="POST">
        <label>Matrícula:</label>
        <input type="text" name="matricula" required>
        <label>Contraseña:</label>
        <input type="password" name="password" required>
        <div class="modal-buttons">
          <button type="submit">Registrar</button>
          <button type="button" class="cancel" onclick="cerrarRegistro()">Cancelar</button>
        </div>
    </form>
  </div>
</div>

<script>
function abrirConsulta() { document.getElementById("consultaBox").style.display = "flex"; }
function cerrarConsulta() { document.getElementById("consultaBox").style.display = "none"; }
function abrirRegistro() { document.getElementById("registroBox").style.display = "flex"; }
function cerrarRegistro() { document.getElementById("registroBox").style.display = "none"; }
</script>

<!-- ======================= WARNINGS ABAJO ======================= -->
<?php if (!empty($warnings)): ?>
<div class="warnings-container">
    <?php foreach ($warnings as $w): ?>
        <div class="warning-box"><?= $w ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</body>
</html>
