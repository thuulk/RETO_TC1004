#include <Wire.h>
#include <ESP8266WiFi.h>
#include <ArduinoJson.h>
#include "wifiPublish.h"
#include "airQualityAnalyzer.h"
#include "bmeAnalyzer.h"
#include "airQualityAnalyzer.h"
#include "alarm.h"
#include "css811Reader.h"

// SET-POINTS
#define TEMP_MAX 35
#define TEMP_MIN 20
#define HUMID_MAX 30
#define HUMID_MIN 12
#define PRESS_MAX 40
#define PRESS_MIN 25

// ALARMS (OUTPUT-PINS)


// ===== bme setup =====
Adafruit_BME280 bme280;
BMEReader bme(bme280);

// ===== Initiliazing PMS5003 (PMserial) =====
// Constructor recomendado por la librería:
// SerialPM pms(PMSx003, RX, TX);
SerialPM serialpm(PMSx003, PMS_RX, PMS_TX);
PMSReader pms(serialpm);

// ===== css setup =====
Adafruit_CCS811 css811;
CSSReader css(css811);

// -------- CONECTAR WIFI --------


// -------- RECONNECT MQTT --------




// ------- ALARMS --------


// ===== SETUP =====
void setup() {
  Serial.begin(115200); // inicializando baud rate

  // ===== pms ======
  serialpm.init();   // configure interanl serial port to 9600

  // ===== bme ======
  if (!bme280.begin(0x76)) { // caso: direccion de memoria del bme no encontrada
    Serial.println("ERROR: No se encontró BME280.");
    while (1);
  }
  Wire.begin(BME_SDA, BME_SCL); // SDA, SCL
  Serial.println("Sensores inicializados correctamente.");

  // ===== ccs =====
  if (!ccs.begin()) {
    Serial.println("No se pudo iniciar el sensor CCS811. Verifica cableado!");
    while (1);
  }

  // Configurar modo de medición (1 lectura/seg)
  ccs.setDriveMode(CCS811_DRIVE_MODE_1SEC);

  // Esperar a que se estabilice (warm-up)
  Serial.println("Esperando a que el sensor esté listo...");
  while (!ccs.available()) delay(100);
  Serial.println("CCS811 listo!");
  
  
  // ===== wifi setup =====
  setup_wifi();
  client.setServer(mqttServer, mqttPort);

}

// ===== LOOP =====
void loop() {
  if (!client.connected()) reconnect();
  client.loop();

  sendSensorData(pms, bme, ccs);
  delay(3000);
  
  

  // ===== Alarms =====
  
  
  // serial monitor 
  Serial.println(bme.toString());
  Serial.println(pms.toString());
  Serial.println(ccs.toString());


}

