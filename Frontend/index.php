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
        <p>Calidad del Aire pm1 (μg/m³)</p>
        <button class="setpoint-btn" onclick="setSetpoint('air')">Setpoint</button>
      </div>

      <div class="gauge-card" id="card-co2">
        <canvas id="co2Gauge"></canvas>
        <p>CO2 (ppm)</p>
        <button class="setpoint-btn" onclick="setSetpoint('co2')">Setpoint</button>
      </div>

      <div class="gauge-card" id="card-tvoc">
        <canvas id="tvocGauge"></canvas>
        <p>TVOC (ppb)</p>
        <button class="setpoint-btn" onclick="setSetpoint('tvoc')">Setpoint</button>
      </div>

    </div>
  </section>
</div>

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
let gauges = {};
let setpointActual = null;

let setpoints = {
  temp: {min:null, max:null},
  hum:  {min:null, max:null},
  pres: {min:null, max:null},
  air:  {min:null, max:null},
  // === NUEVOS SETPOINTS ===
  co2:  {min:null, max:null},
  tvoc: {min:null, max:null}
};

let valores = {
  temperatura: 20,
  humedad: 40,
  presion: 900,
  calidad: 30,
  // === NUEVOS VALORES INICIALES ===
  co2: 450, // Partes por millón
  tvoc: 100 // Partes por billón
};

const codigoAlarma = "1234";
let alarmaActiva = false;

// ========= GAUGES ===========
function createOrUpdateGauge(id, value, max) {
  const canvas = document.getElementById(id);
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
    air: "Calidad del aire (ppm)",
    // === NUEVOS NOMBRES ===
    co2: "Dióxido de Carbono (ppm)",
    tvoc: "Compuestos Orgánicos Volátiles (ppb)"
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

  if (sp.min !== null && valor < sp.min) {
    activarAlerta(tipo, valor);
    return;
  }

  if (sp.max !== null && valor > sp.max) {
    activarAlerta(tipo, valor);
    return;
  }
}

// ========= SIMULACIÓN - INCLUYE CO2 y TVOC ===========
function simularDatos() {
  // Simulación de valores existentes (con rangos más centrados)
  valores.temperatura += Math.random() * 2 - 1;
  valores.humedad += Math.random() * 2 - 1;
  valores.presion += Math.random() * 3 - 1.5;
  valores.calidad += Math.random() * 4 - 2;

  // === SIMULACIÓN DE CO2 y TVOC ===
  valores.co2 += Math.random() * 10 - 5;
  valores.tvoc += Math.random() * 5 - 2.5;

  // Asegurar mínimos (por ejemplo, CO2 no baja de 350)
  valores.co2 = Math.max(350, valores.co2);
  valores.tvoc = Math.max(0, valores.tvoc);

  // Actualización de Gauges existentes
  createOrUpdateGauge('tempGauge', valores.temperatura, 50);
  createOrUpdateGauge('humGauge', valores.humedad, 100);
  createOrUpdateGauge('presGauge', valores.presion, 1100);
  createOrUpdateGauge('airGauge', valores.calidad, 500);

  // === ACTUALIZACIÓN DE GAUGES NUEVOS ===
  createOrUpdateGauge('co2Gauge', valores.co2, 2000); // Máximo de 2000 ppm
  createOrUpdateGauge('tvocGauge', valores.tvoc, 600); // Máximo de 600 ppb

  // Verificación de Alarma existentes
  verificarAlarma('temp', valores.temperatura);
  verificarAlarma('hum', valores.humedad);
  verificarAlarma('pres', valores.presion);
  verificarAlarma('air', valores.calidad);

  // === VERIFICACIÓN DE ALARMA NUEVAS ===
  verificarAlarma('co2', valores.co2);
  verificarAlarma('tvoc', valores.tvoc);
}

setInterval(simularDatos, 2000);

// ======== REGISTRO EMPLEADO ==========
function abrirRegistro() {
    document.getElementById("registroBox").style.display = "flex";
}

function cerrarRegistro() {
    document.getElementById("registroBox").style.display = "none";
}

</script>

</body>
</html>