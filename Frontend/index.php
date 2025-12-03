<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard de Monitoreo</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- ====== MODALES MEJORADOS ====== -->
  <style>

    /* --- Fondo borroso + animación --- */
    .alert-box {
      display: none;
      position: fixed;
      inset: 0;
      backdrop-filter: blur(8px);
      background: rgba(0,0,0,0.45);
      justify-content: center;
      align-items: center;
      z-index: 9999;
      animation: fadeIn 0.25s ease-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to   { opacity: 1; }
    }

    /* --- Contenido del modal, más grande y bonito --- */
    .alert-content {
      background: #ffffffee;
      padding: 35px;
      border-radius: 22px;
      width: 420px;
      max-width: 90%;
      text-align: center;
      color: #000;
      box-shadow: 0 12px 40px rgba(0,0,0,0.25);
      animation: pop 0.3s ease-out;
    }

    @keyframes pop {
      from { transform: scale(0.75); opacity: 0; }
      to   { transform: scale(1); opacity: 1; }
    }

    .alert-content h3 {
      font-size: 2rem;
      margin-bottom: 10px;
      color: #023047;
    }

    .alert-content input,
    .alert-content label {
      color: #000;
    }

    .alert-content input {
      width: 92%;
      padding: 12px;
      border-radius: 10px;
      border: 1px solid #b7b7b7;
      margin-bottom: 15px;
      font-size: 1.6rem;
    }

    /* Botones bonitos */
    .modal-buttons {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-top: 10px;
    }

    .modal-buttons button {
      padding: 12px;
      border-radius: 12px;
      border: none;
      font-size: 1.6rem;
      cursor: pointer;
      transition: 0.2s;
      background: var(--azul);
      color: white;
      transition: background-color 0.3s ease;
    }

    .modal-buttons button:hover {
      background: #147a94;
      transform: scale(1.03);
    }

    .modal-buttons button.cancel {
      background: #888888;
    }

    .modal-buttons button.cancel:hover {
      background: #6e6e6e;
    }

    /* Alarma */
    .alarm {
      background-color: #ffb4b4 !important;
      box-shadow: 0 0 20px rgba(255, 0, 0, 0.6);
    }
  </style>

</head>

<body class="dashboard-page">

<header>
   <img src="img/BioAirSolutionsLogo.png" alt="BioAirSolution" class="logo">
</header>

<nav>
  <div class="nav-links">
    <a href="index.php"><p class="activo">Inicio</p></a>
    <a href="historial.php"><p>Historial</p></a>
  </div>

  <div class="nav-right">
      <button class="nav-btn" onclick="abrirRegistro()">
          Registrar empleado
      </button>

      <a href="logout.php">
        <button class="logout-btn">Cerrar sesión</button>
      </a>
  </div>
</nav>

<div class="container">
  <section class="dashboard">
    <h2>Bienvenido al Sistema de Monitoreo</h2>
    <p class="subtitulo">Monitoreo en tiempo real de las condiciones ambientales</p>

    <div class="gauges-container">

      <div class="gauge-card" id="card-temp">
        <canvas id="tempGauge"></canvas>
        <p>Temperatura (°C)</p>
        <button class="setpoint-btn" onclick="setSetpoint('temp')">Setpoint</button>
      </div>

      <div class="gauge-card" id="card-hum">
        <canvas id="humGauge"></canvas>
        <p>Humedad (%)</p>
        <button class="setpoint-btn" onclick="setSetpoint('hum')">Setpoint</button>
      </div>

      <div class="gauge-card" id="card-pres">
        <canvas id="presGauge"></canvas>
        <p>Presión (hPa)</p>
        <button class="setpoint-btn" onclick="setSetpoint('pres')">Setpoint</button>
      </div>

      <div class="gauge-card" id="card-air">
        <canvas id="airGauge"></canvas>
        <p>Calidad del Aire (ppm)</p>
        <button class="setpoint-btn" onclick="setSetpoint('air')">Setpoint</button>
      </div>

    </div>
  </section>
</div>

<!-- ================== MODALES ================== -->

<!-- SETPOINT -->
<div id="setpointBox" class="alert-box">
  <div class="alert-content">
    <h3>⚙ Configurar setpoint</h3>
    <p id="setpointLabel"></p>

    <label>Mínimo:</label>
    <input type="number" id="setpointMin">

    <label>Máximo:</label>
    <input type="number" id="setpointMax">

    <div class="modal-buttons">
      <button onclick="guardarSetpoint()">Guardar</button>
      <button class="cancel" onclick="cerrarSetpoint()">Cancelar</button>
    </div>

  </div>
</div>

<!-- ALERTA -->
<div id="alertBox" class="alert-box">
  <div class="alert-content">
    <h3>⚠ ALARMA ACTIVADA</h3>
    <p id="alertMessage"></p>

    <label>Código para desactivar:</label>
    <input type="password" id="alertCode">

    <div class="modal-buttons">
      <button onclick="validarCodigo()">Desactivar</button>
    </div>

  </div>
</div>

<!-- REGISTRO -->
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

<!-- ================= JS ================== -->
<script>
let gauges = {};
let setpointActual = null;

// Rango de setpoints (configurable desde modal)
let setpoints = {
<<<<<<< Updated upstream
  temp: {min:null, max:null},
  hum:  {min:null, max:null},
  pres: {min:null, max:null},
  air:  {min:null, max:null}
=======
  temp: {min: null, max: null},
  hum:  {min: null, max: null},
  pres: {min: null, max: null},
  air:  {min: null, max: null},
  co2:  {min: null, max: null},
  tvoc: {min: null, max: null}
>>>>>>> Stashed changes
};

// Valores actuales de los sensores (se actualizan con WebSocket)
let valores = {
  temperatura: 20,
  humedad: 40,
  presion: 900,
<<<<<<< Updated upstream
  calidad: 30
=======
  calidad: 30,
  co2: 450,  // ppm
  tvoc: 100  // ppb
>>>>>>> Stashed changes
};

const codigoAlarma = "1234";
let alarmaActiva = false;

// ========= GAUGES ===========
function createOrUpdateGauge(id, value, max) {
  const canvas = document.getElementById(id);
  if (!canvas) return;

  const ctx = canvas.getContext('2d');

  const percent = (value / max) * 100;
  const color = percent < 60 ? '#219EBC' : percent < 80 ? '#FFB703' : '#FB8500';

  if (gauges[id]) {
    gauges[id].data.datasets[0].data = [value, max - value];
    gauges[id].data.datasets[0].backgroundColor[0] = color;
    gauges[id].currentValue = value;
    gauges[id].update();
    return;
  }

  gauges[id] = new Chart(ctx, {
    type: 'doughnut',
    data: {
      datasets: [{
        data: [value, max - value],
        backgroundColor: [color, '#e0e0e0'],
        borderWidth: 0
      }]
    },
    options: {
      circumference: 180,
      rotation: 270,
      cutout: '80%',
      plugins: {
        tooltip: { enabled: false },
        legend: { display: false }
      }
    },
    plugins: [{
      id: 'centerText',
      afterDraw(chart) {
        const ctx = chart.ctx;
        ctx.save();
        ctx.font = '18px Arial';
        ctx.fillStyle = '#023047';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(
          chart.currentValue?.toFixed(1) || '0.0',
          chart.width / 2,
          chart.height / 1.4
        );
        ctx.restore();
      }
    }]
  });

  gauges[id].currentValue = value;
}

// ========= SETPOINT ===========
function setSetpoint(type) {
  setpointActual = type;

  const names = {
    temp: "Temperatura (°C)",
    hum: "Humedad (%)",
    pres: "Presión (hPa)",
<<<<<<< Updated upstream
    air: "Calidad del aire (ppm)"
=======
    air: "Calidad del aire (ppm)",
    co2: "Dióxido de Carbono (ppm)",
    tvoc: "Compuestos Orgánicos Volátiles (ppb)"
>>>>>>> Stashed changes
  };

  document.getElementById("setpointLabel").innerText = names[type];

  document.getElementById("setpointMin").value = setpoints[type].min ?? "";
  document.getElementById("setpointMax").value = setpoints[type].max ?? "";

  document.getElementById("setpointBox").style.display = "flex";
}

function guardarSetpoint() {
  const min = document.getElementById("setpointMin").value;
  const max = document.getElementById("setpointMax").value;

  if (min === "" || max === "") {
    alert("Debes ingresar mínimo y máximo.");
    return;
  }

  setpoints[setpointActual].min = parseFloat(min);
  setpoints[setpointActual].max = parseFloat(max);

  cerrarSetpoint();
}

function cerrarSetpoint() {
  document.getElementById("setpointBox").style.display = "none";
}

// ========= ALERTA ===========
function activarAlerta(tipo, valor) {
  if (alarmaActiva) return;

  alarmaActiva = true;

  document.getElementById("alertMessage").innerText =
    `El sensor de ${tipo.toUpperCase()} salió del rango (${valor.toFixed(1)}).`;

  document.getElementById("alertBox").style.display = "flex";
}

function validarCodigo() {
  let code = document.getElementById("alertCode").value;

  if (code === codigoAlarma) {
    alarmaActiva = false;
    document.getElementById("alertBox").style.display = "none";
  } else {
    alert("Código incorrecto.");
  }
}

// ========= VERIFICAR ALARMA ===========
function verificarAlarma(tipo, valor) {
  const sp = setpoints[tipo];

  if (!sp) return;

  if (sp.min !== null && valor < sp.min) {
    activarAlerta(tipo, valor);
    return;
  }

  if (sp.max !== null && valor > sp.max) {
    activarAlerta(tipo, valor);
    return;
  }
}

<<<<<<< Updated upstream
// ========= SIMULACIÓN ===========
function simularDatos() {
  valores.temperatura += Math.random() * 2;
  valores.humedad += Math.random() * 2;
  valores.presion += Math.random() * 3;
  valores.calidad += Math.random() * 4;

=======
// ========= ACTUALIZAR GAUGES DESDE VALORES ===========
function actualizarGaugesDesdeValores() {
  // Gauges BME
>>>>>>> Stashed changes
  createOrUpdateGauge('tempGauge', valores.temperatura, 50);
  createOrUpdateGauge('humGauge',  valores.humedad,     100);
  createOrUpdateGauge('presGauge', valores.presion,     1100);
  createOrUpdateGauge('airGauge',  valores.calidad,     500);

<<<<<<< Updated upstream
=======
  // Gauges CO2 / TVOC (si empiezas a mandarlos en el JSON)
  createOrUpdateGauge('co2Gauge',  valores.co2,  2000);
  createOrUpdateGauge('tvocGauge', valores.tvoc, 600);

  // Alarmas
>>>>>>> Stashed changes
  verificarAlarma('temp', valores.temperatura);
  verificarAlarma('hum',  valores.humedad);
  verificarAlarma('pres', valores.presion);
<<<<<<< Updated upstream
  verificarAlarma('air', valores.calidad);
=======
  verificarAlarma('air',  valores.calidad);
  verificarAlarma('co2',  valores.co2);
  verificarAlarma('tvoc', valores.tvoc);
>>>>>>> Stashed changes
}

// ======== REGISTRO EMPLEADO ==========
function abrirRegistro() {
    document.getElementById("registroBox").style.display = "flex";
}

function cerrarRegistro() {
    document.getElementById("registroBox").style.display = "none";
}

// ======== WEBSOCKET (DATOS REALES) ==========
/*
  Tu ESP manda un JSON así (según wifi.h):

  {
    "temperatura": <float>,
    "humedad":     <float>,
    "presion":     <float>,
    "pm1":         <int>,
    // "co2":      <int>,   // cuando lo actives
    // "tvoc":     <int>
  }
*/

// Ajusta la URL si usas Node-RED:
// var socket = new WebSocket('ws://' + window.location.hostname + ':1880/dashboard');
var socket = new WebSocket('ws://' + window.location.hostname + ':1880/dashboard');

socket.onmessage = function(event) {
  try {
    var data = JSON.parse(event.data);

    // Mapeo JSON -> objeto valores
    if (typeof data.temperatura === 'number') {
      valores.temperatura = data.temperatura;
    }
    if (typeof data.humedad === 'number') {
      valores.humedad = data.humedad;
    }
    if (typeof data.presion === 'number') {
      valores.presion = data.presion;
    }
    if (typeof data.pm1 === 'number') {
      valores.calidad = data.pm1; // calidad del aire basada en pm1
    }

    // Cuando empieces a mandarlos:
    if (typeof data.co2 === 'number') {
      valores.co2 = data.co2;
    }
    if (typeof data.tvoc === 'number') {
      valores.tvoc = data.tvoc;
    }

    // Actualiza gauges y alarmas con los datos reales
    actualizarGaugesDesdeValores();

  } catch (e) {
    console.error("Error parseando mensaje WebSocket:", e, event.data);
  }
};

socket.onopen = function() {
  console.log("Conexión WebSocket abierta");
};

socket.onclose = function() {
  console.log("Conexión WebSocket cerrada");
};

socket.onerror = function(error) {
  console.log("Error en WebSocket:", error);
};
</script>

</body>
</html>
