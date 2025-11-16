#include <Wire.h>
#include <Adafruit_Sensor.h>
#include <Adafruit_BME280.h>
#define SEALEVELPRESSURE_HPA (1010.80)
#define LED1 D0
#define LED2 D1
#define LED3 D2
#define BUZZER D3
#define SCLBME280 D5 // GPIO14, cable azul
#define SDABME280 D6 // GPIO12, cable purpura
Adafruit_BME280 bme;


void setup() {
  Serial.begin(115200);

  // Cambiar los pines I2C aquí 👇
  Wire.begin(SDABME280, SCLBME280);  

  if (!bme.begin(0x76)) {
    Serial.println("No se detecta el BME280");
    while (1);
  }

  pinMode(LED1, OUTPUT);
  pinMode(LED2, OUTPUT);
  pinMode(LED3, OUTPUT); 
  //pinMode(LED4, OUTPUT);
  //pinMode(LED5, OUTPUT);
  //pinMode(LED6, OUTPUT); // empieza apagado (activo-bajo)
  digitalWrite(LED1, LOW);
  digitalWrite(LED2, LOW);
  digitalWrite(LED3, LOW);
  //digitalWrite(LED4, LOW);
  //digitalWrite(LED5, LOW);
  //digitalWrite(LED6, LOW);
  
}

void loop() {
  float t = bme.readTemperature();         // °C
  float humedad = bme.readHumidity();                // %
  float presion = bme.readPressure() / 100.0F;       // Pa → hPa
  float altitud = bme.readAltitude(SEALEVELPRESSURE_HPA); // metros

  // Mostrar en el monitor serial
  Serial.print("🌡Temp: ");
  Serial.print(t);
  Serial.print(" °C  |  💧 Hum: ");
  Serial.print(humedad);
  Serial.print(" %  |  🌬 Presión: ");
  Serial.print(presion);
  Serial.print(" hPa  |  🏔 Altitud: ");
  Serial.print(altitud);
  Serial.println(" m");

  delay(3000);

  // SE DECLARA EN QUE RANGO SE PRENDERA CADA LED SEGUN LA TEMPERATURA QUE TENGA EL SENSOR


}