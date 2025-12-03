<?php
session_start();

// Si quieres mantener validación de sesión:
if (!isset($_SESSION["username"])) {
    header("Location: login.html");
    exit();
}

// Si no vas a usar warnings, define el arreglo vacío para evitar notices
$warnings = [];
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
        <table id="tabla-historial">
          <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Humedad</th>
                <th>Temperatura</th>
                <th>Calidad Aire</th>
                <th>Presión</th>
                <th>CO₂</th>
                <th>TVOC</th>
            </tr>
          </thead>
          <tbody id="tabla-historial-body">
            <!-- El cuerpo lo llenará JS, así que puedes borrar TODO el bloque PHP de aquí adentro -->
          </tbody>
      </table>

    </div>
</div>

<!-- ======================= MODALES ======================= -->
<div id="consultaBox" class="alert-box">
  <div class="alert-content">
    <h3>Consultar registros</h3>
    <form onsubmit="event.preventDefault(); enviarConsultaHistorial(this);">
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
            <?php for ($h=0;$h<24;$h++): $ho = str_pad($h,2,"0",STR_PAD_LEFT).":59:59"; ?>
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

// ======================= WEBSOCKET HISTORIAL =======================

// Conexión al WebSocket de Node-RED para historial
var wsHist = new WebSocket('ws://' + window.location.hostname + ':1880/historial');

wsHist.onopen = function () {
  console.log('WS historial conectado');
};

wsHist.onerror = function (e) {
  console.error('WS historial ERROR:', e);
};

wsHist.onclose = function (e) {
  console.warn('WS historial cerrado. readyState =', wsHist.readyState, e);
};

// Función que se llama cuando envías el formulario del modal
function enviarConsultaHistorial(form) {
  var fecha      = form.fecha.value;
  var horaInicio = form.hora_inicio.value;
  var horaFin    = form.hora_fin.value;

  console.log('enviarConsultaHistorial()', { fecha, horaInicio, horaFin });

  if (!fecha || !horaInicio || !horaFin) {
    alert("Completa fecha y rango de horas");
    return;
  }

  if (wsHist.readyState !== WebSocket.OPEN) { // 1
    console.error('WS historial NO está conectado. readyState =', wsHist.readyState);
    alert('El WebSocket de historial no está conectado. Revisa Node-RED y el puerto 1880.');
    return;
  }

  var msg = {
    fecha: fecha,
    hora_inicio: horaInicio,
    hora_fin: horaFin
  };

  console.log("Enviando consulta historial:", msg);
  wsHist.send(JSON.stringify(msg));
}

// Cuando Node-RED responde con las filas del historial
wsHist.onmessage = function (event) {
  console.log("Historial recibido:", event.data);

  var rows = JSON.parse(event.data); // array de objetos
  var tbody = document.getElementById('tabla-historial-body');
  tbody.innerHTML = '';

  if (!Array.isArray(rows) || rows.length === 0) {
    var tr = document.createElement('tr');
    tr.innerHTML = '<td colspan="8">No hay resultados en ese rango.</td>';
    tbody.appendChild(tr);
    return;
  }

  rows.forEach(function (r) {
    var tr = document.createElement('tr');
    tr.innerHTML =
      '<td>' + r.fecha         + '</td>' +
      '<td>' + r.hora          + '</td>' +
      '<td>' + r.humedad       + '</td>' +
      '<td>' + r.temperatura   + '</td>' +
      '<td>' + r.calidad_Aire  + '</td>' +
      '<td>' + r.presion       + '</td>' +
      '<td>' + r.co2           + '</td>' +
      '<td>' + r.tvoc          + '</td>';
    tbody.appendChild(tr);
  });

  // Cierra el modal cuando ya se llenó la tabla
  cerrarConsulta();
};
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
