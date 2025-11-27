#include <Wire.h>
#include <Adafruit_Sensor.h>
#include <Adafruit_BME280.h>
#include <ESP8266WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <PMserial.h>
#include "wifi.h" // rename wifi_example.h or change the include
#include "mqtt.h" // rename wifi_example.h or change the includ

// SET-POINTS
#define TEMP_MAX 35
#define TEMP_MIN 20
#define HUMID_MAX 30
#define HUMID_MIN 12
#define PRESS_MAX 40
#define PRESS_MIN 25
#define PM1_MAX 5.0f    // µg/m^3
#define PM25_MAX 5.0f   // µg/m^3
#define PM10_MAX 15.0f  // µg/m^3
#define SMALL_COUNT_MAX 3000.0f  // p03 + p05 + p10
#define LARGE_COUNT_MAX 500.0f   // p25 + p50 + p100

// SENSORS PINS
#define BME_SCL D1    // Purple jumper
#define BME_SDA D2    // Blue jumper
#define PMS_RX D3
#define PMS_TX D4

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
SerialPM pms(PMSx003, PMS_RX, PMS_TX);

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

// -------- LEER PMS5003 (usando PMserial) --------
bool readPMS() {
  // Dispara la lectura y decodifica el último frame del sensor
  pms.read();

  // Si no hay medición válida de PM/NC, regresamos false
  if (!pms.has_particulate_matter() || !pms.has_number_concentration()) {
    // Si quieres debug más fino:
    Serial.print("PMS error status: ");
    Serial.println(pms.status);
    return false;
  }



  Serial.println("----- Datos PMS5003 (PMserial) -----");
  Serial.printf("PM1: %u  PM2.5: %u  PM10: %u\n", pm1, pm25, pm10);
  Serial.printf(
    "0.3um: %u  0.5um: %u  1.0um: %u  2.5um: %u  5.0um: %u  10um: %u\n",
    p03, p05, p10, p25, p50, p100
  );

  return true;
}

// -------- ENVIAR JSON --------
void sendSensorData(PMserial& pms) {
  float temp = bme.readTemperature();
  float hum  = bme.readHumidity();
  float pres = bme.readPressure() / 100.0F;

  if (isnan(temp) || isnan(hum) || isnan(pres)) {
    Serial.println("Error leyendo BME280.");
    return;
  }

  // Actualiza valores del PMS (si falla, deja los últimos)
  readPMS();

  StaticJsonDocument<350> doc;

  doc["temperatura"] = temp;
  doc["humedad"]     = hum;
  doc["presion"]     = pres;

  // PM estándar
  doc["pm1"]  = pm1;
  doc["pm25"] = pm25;
  doc["pm10"] = pm10;

  // Partículas por tamaño
  doc["p03"]  = p03;
  doc["p05"]  = p05;
  doc["p10"]  = p10;   // 1 µm
  doc["p25"]  = p25;
  doc["p50"]  = p50;
  doc["p100"] = p100;

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
  pms.init();   // configura el puerto serie interno a 9600
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
  bool isReadingPMS = readPMS();

  // -- Alarms -- 
  alarms(temp, humid, press);
  
  // serial monitor 
  Serial.print("🌡Temp: " + String(temp) +  " °C | ");
  Serial.print("💧 Hum: " + String(humid) + " % | "); 
  Serial.print("🌬 Presión: " + String(press) + "hPa\n");

}

