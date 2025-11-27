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

// ===== bme setup =====
  Adafruit_BME280 bme280;
  BMEReader bme(bme280);

// ===== Initiliazing PMS5003 (PMserial) =====
// Constructor recomendado por la librería:
// SerialPM pms(PMSx003, RX, TX);
SerialPM serialpm(PMSx003, PMS_RX, PMS_TX);
PMSReader pms(serialpm);

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

// ===== SEND JSON =====
void sendSensorData(PMSReader& aqReader, BMEReader& atReader) {
  
  // ===== Sensor readings plus reading integrity check =====
  if (!aqReader.updateData()) Serial.println("failed reading at PMS5003");
  if (!atReader.updateData()) Serial.println("failed reading at BME280");

  // ===== referencing the read data =====
  const PMSData& pmsData = aqReader.getData();
  const BMEData& bmeData = atReader.getData();

  // ===== defining JSON document for packaging the data =====
  StaticJsonDocument<350> doc;
  
  // ===== Parsing the data to a JSON file =====
  doc["temperatura"] = bmeData.temp;
  doc["humedad"]     = bmeData.humid;
  doc["presion"]     = bmeData.press;

  //  ===== standard PM =====
  doc["pm1"]  = pmsData.pm1;
  doc["pm25"] = pmsData.pm25;
  doc["pm10"] = pmsData.pm10;

  // ===== standard P =====
  doc["p03"]  = pmsData.p03;
  doc["p05"]  = pmsData.p05;
  doc["p10"]  = pmsData.p10;   // 1 µm
  doc["p25"]  = pmsData.p25;
  doc["p50"]  = pmsData.p50;
  doc["p100"] = pmsData.p100;

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

// ===== SETUP =====
void setup() {
  Serial.begin(115200); // inicializando baud rate

  // ===== wifi setup =====
  //setup_wifi();
  //client.setServer(mqttServer, mqttPort);

}

// ===== LOOP =====
void loop() {
  //if (!client.connected()) reconnect();
  //client.loop();

  sendSensorData(pms, bme);
  delay(3000);

  // ===== Alarms =====
  
  
  // serial monitor 
  Serial.println(pms.toString());
  Serial.println(bme.toString());

}

