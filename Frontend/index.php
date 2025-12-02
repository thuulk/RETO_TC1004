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
</head>

<body class="dashboard-page">
    <header>
        <img src="img/BioAirSolutionsLogo.png" alt="BioAirSolution" class="logo">
    </header>
    <nav>
      <div class="nav-links">
          <p class="activo">Inicio</p>
          <p>Historial</p>
      </div>
      <a href="logout.php"><button class="logout-btn">Cerrar sesión</button></a>
    </nav>
  <div class="container">
    <section class="dashboard">
      <h2>Bienvenido al Sistema de Monitoreo</h2>
      <p>Monitoreo en tiempo real de las condiciones ambientales</p>

      <!-- 🧭 DASHBOARD -->
      <div class="gauges-wrapper">
    <div class="gauges-container">
        <div class="gauge-card">
          <canvas id="tempGauge"></canvas>
          <p>Temperatura (°C)</p>
        </div>
        <div class="gauge-card">
          <canvas id="humGauge"></canvas>
          <p>Humedad (%)</p>
        </div>
        <div class="gauge-card">
          <canvas id="presGauge"></canvas>
          <p>Presión (hPa)</p>
        </div>
        <div class="gauge-card">
          <canvas id="airGauge"></canvas>
          <p>Calidad del Aire (ppm)</p>
        </div>
        </div>
      </div>
    </section>
  </div>

  <script>
  // ====== FUNCIÓN PARA CREAR UN GAUGE ======
  function createGauge(id, value, max) {
    const ctx = document.getElementById(id).getContext('2d');

    let color;
    const percent = (value / max) * 100;
    if (percent < 60) color = '#219EBC';      // azul
    else if (percent < 80) color = '#FFB703'; // amarillo
    else color = '#FB8500';                   // rojo

    return new Chart(ctx, {
      type: 'doughnut',
      data: {
        datasets: [{
          data: [value, Math.max(max - value, 0)],
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
        },
        responsive: true
      },
      plugins: [{
        id: 'text',
        beforeDraw: chart => {
          const { width, height } = chart;
          const ctx = chart.ctx;
          ctx.restore();
          const fontSize = (height / 115).toFixed(2);
          ctx.font = `${fontSize}em sans-serif`;
          ctx.textBaseline = 'middle';
          const text = chart.data.datasets[0].data[0].toFixed(1);
          const textX = Math.round((width - ctx.measureText(text).width) / 2);
          const textY = height / 1.4;
          ctx.fillText(text, textX, textY);
          ctx.save();
        }
      }]
    });
  }

  // ====== FUNCIÓN PARA ACTUALIZAR UN GAUGE EXISTENTE ======
  function updateGauge(chart, value, max) {
    if (!chart) return;
    const dataset = chart.data.datasets[0];

    // actualizar datos
    dataset.data[0] = value;
    dataset.data[1] = Math.max(max - value, 0);

    // recalcular color según porcentaje
    const percent = (value / max) * 100;
    let color;
    if (percent < 60) color = '#219EBC';
    else if (percent < 80) color = '#FFB703';
    else color = '#FB8500';

    dataset.backgroundColor[0] = color;

    chart.update();
  }

  // ====== VALORES MÁXIMOS DE CADA GAUGE ======
  const MAX_TEMP = 50;    // ajusta si tu rango es otro
  const MAX_HUM  = 100;
  const MAX_PRES = 1100;
  const MAX_AIR  = 500;   // placeholder por ahora

  // ====== CREAR GAUGES INICIALES (VALORES EN 0) ======
  let tempGauge  = createGauge('tempGauge', 0, MAX_TEMP);
  let humGauge   = createGauge('humGauge', 0, MAX_HUM);
  let presGauge  = createGauge('presGauge', 0, MAX_PRES);
  let airGauge   = createGauge('airGauge', 0, MAX_AIR); // lo dejamos quieto por ahora

  // ====== WEBSOCKET HACIA NODE-RED ======
  // Asegúrate que tu nodo WebSocket en Node-RED está en:
  //   ws://<tu-host>:1880/temperatura
  // y que envía un JSON como:
  //   { "temperatura": 23.5, "humedad": 45.2, "presion": 1013.2 }
  const socketUrl = 'ws://' + window.location.hostname + ':1880/temperatura';
  const socket = new WebSocket(socketUrl);

  socket.onopen = function () {
    console.log('WebSocket conectado a', socketUrl);
  };

  socket.onmessage = function (event) {
    try {
      const data = JSON.parse(event.data);

      // BME280 -> temperatura, humedad, presion
      if (typeof data.temperatura === 'number') {
        updateGauge(tempGauge, data.temperatura, MAX_TEMP);
      }
      if (typeof data.humedad === 'number') {
        updateGauge(humGauge, data.humedad, MAX_HUM);
      }
      if (typeof data.presion === 'number') {
        updateGauge(presGauge, data.presion, MAX_PRES);
      }

      // Si más adelante quieres usar PMS para calidad del aire,
      // aquí podrías hacer algo como:
      // if (typeof data.calidadAire === 'number') {
      //   updateGauge(airGauge, data.calidadAire, MAX_AIR);
      // }

    } catch (e) {
      console.error('Error al parsear mensaje WebSocket:', e, event.data);
    }
  };

  socket.onclose = function () {
    console.log('WebSocket cerrado');
  };

  socket.onerror = function (error) {
    console.error('Error en WebSocket:', error);
  };
</script>
</body>
</html>
