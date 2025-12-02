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
  function createGauge(id, value, max) {
    const ctx = document.getElementById(id).getContext('2d');

    let color;
    const percent = (value / max) * 100;
    if (percent < 60) color = '#219EBC'; // azul
    else if (percent < 80) color = '#FFB703'; // amarillo
    else color = '#FB8500'; // rojo

    return new Chart(ctx, {
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

  // Datos simulados (luego vendrán de la base de datos)
  const temperatura = 27.5;
  const humedad = 65;
  const presion = 500;
  const calidadAire = 120;

  // Crear los gauges
  createGauge('tempGauge', temperatura, 50);
  createGauge('humGauge', humedad, 100);
  createGauge('presGauge', presion, 1100);
  createGauge('airGauge', calidadAire, 500);
  </script>
</body>
</html>
