#include <Wire.h>
#include <ESP8266WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include "wifi.h" // rename wifi_example.h or change the include
#include "mqtt.h" // rename wifi_example.h or change the includ3
#include "airQualityAnalyzer.h"
#include "bmeAnalyzer.h"

// SET-POINTS
#define TEMP_MAX 35
#define TEMP_MIN 20
#define HUMID_MAX 30
#define HUMID_MIN 12
#define PRESS_MAX 40
#define PRESS_MIN 25

// SENSORS PINS
#define BME_SCL D1    // Purple jumper
#define BME_SDA D2    // Blue jumper

// ALARMS (OUTPUT-PINS)
#define GREEN_LED D5
#define YELLOW_LED D6
#define RED_LED D7
#define BUZZER D8


// -------- CONFIGURACIÓN WIFI --------
const char* ssid     = WIFI_SSID;
const char* password = WIFI_PASSWORD;

// -------- CONFIGURACIÓN MQTT --------
const char* mqttServer = "172.20.10.2";
const int mqttPort = 1883;
const char* topic = "sensores/diego";
WiFiClient espClient;
PubSubClient client(espClient);

// -------- Initializing BME280 I2C ------------
Adafruit_BME280 bme;

// -------- Initiliazing PMS5003 (PMserial) --------
// Constructor recomendado por la librería:
// SerialPM pms(PMSx003, RX, TX);
SerialPM serialpm(PMSx003, PMS_RX, PMS_TX);
PMSreader pms(serialpm);

// -------- CONECTAR WIFI --------
void setup_wifi() {
  Serial.println();
  Serial.print("Conectando a ");
  Serial.println(ssid);

  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nWiFi conectado!");
  Serial.print("IP asignada: ");
  Serial.println(WiFi.localIP());
}

// -------- RECONNECT MQTT --------
void reconnect() {
  while (!client.connected()) {
    Serial.print("Intentando conectar al broker MQTT...");

    if (client.connect("ESP8266_SENSORES")) {
      Serial.println("Conectado!");
    } else {
      Serial.print("Fallo, rc=");
      Serial.print(client.state());
      Serial.println(" — Reintentando...");
      delay(2000);
    }
  }
}

// -------- ENVIAR JSON --------
void sendSensorData(PMSreader& aqReader) {
  float temp = bme.readTemperature();
  float hum  = bme.readHumidity();
  float pres = bme.readPressure() / 100.0F;

  if (isnan(temp) || isnan(hum) || isnan(pres)) Serial.println("Error leyendo BME280.");
  if (!aqReader.updateData()) Serial.println("Error leyendo PMS5003");

  const PMSdata& data = aqReader.getData();

  // Actualiza valores del PMS (si falla, deja los últimos)

  StaticJsonDocument<350> doc;
  

  doc["temperatura"] = temp;
  doc["humedad"]     = hum;
  doc["presion"]     = pres;

  // PM estándar
  doc["pm1"]  = data.pm1;
  doc["pm25"] = data.pm25;
  doc["pm10"] = data.pm10;

  // Partículas por tamaño
  doc["p03"]  = data.p03;
  doc["p05"]  = data.p05;
  doc["p10"]  = data.p10;   // 1 µm
  doc["p25"]  = data.p25;
  doc["p50"]  = data.p50;
  doc["p100"] = data.p100;

  char buffer[400];
  serializeJson(doc, buffer);

  if (client.publish(topic, buffer)) {
    Serial.println("JSON enviado:");
    Serial.println(buffer);
  } else {
    Serial.println("Error enviando JSON.");
  }
}

// ------- ALARMS --------
void alarms(const float& temp, const float& humid, const float& press) {
  if (press < PRESS_MIN || press > PRESS_MAX) digitalWrite(YELLOW_LED, HIGH);
  else digitalWrite(YELLOW_LED, LOW);

  if (humid < HUMID_MIN || humid > HUMID_MAX) digitalWrite(RED_LED, HIGH);
  else digitalWrite(RED_LED, LOW);

  if (temp < TEMP_MIN || temp > TEMP_MAX) digitalWrite(BUZZER, HIGH);
  else digitalWrite(BUZZER, LOW);

}

// -------- SETUP --------
void setup() {
  Serial.begin(115200); // inicializando baud rate

  // ---- wifi setup ----
  //setup_wifi();
  //client.setServer(mqttServer, mqttPort);

  /// ---- bme setup ----
  if (!bme.begin(0x76)) { // caso: direccion de memoria del bme no encontrada
    Serial.println("ERROR: No se encontró BME280.");
    while (1);
  }

  Wire.begin(BME_SDA, BME_SCL); // SDA, SCL

  // ---- PMS5003 setup vía PMserial ----
  serialpm.init();   // configura el puerto serie interno a 9600
  Serial.println("Sensores inicializados correctamente.");

}

// -------- LOOP --------
void loop() {
  //if (!client.connected()) reconnect();
  //client.loop();

  //sendSensorData();
  delay(3000);

  // ------ sensors readings -------
  float temp = bme.readTemperature();
  float humid = bme.readHumidity();
  float press = bme.readPressure() / 100.0f;

  // -- Alarms -- 
  alarms(temp, humid, press);
  
  // serial monitor 
  Serial.print("🌡Temp: " + String(temp) +  " °C | ");
  Serial.print("💧 Hum: " + String(humid) + " % | "); 
  Serial.print("🌬 Presión: " + String(press) + "hPa\n");

}

