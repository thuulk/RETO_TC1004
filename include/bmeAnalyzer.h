#ifndef INCLUDE_BMEANALYZER_H
#define INCLUDE_BMEANALYZER_H
#include <Adafruit_Sensor.h>
#include <Adafruit_BME280.h>

// ===== SENSORS PINS =====
#define BME_SCL D1    // Purple jumper
#define BME_SDA D2    // Blue jumper

// ===== Presion de hermosillo =====
#define SEALEVELPRESSURE_HPA (1010.80) 

using namespace std;


struct BMEData {
    float temp;
    float humid;
    float press;
    bool isValidRead = false;
};

enum class BMEDataQuality {
    ERROR,
    SAFE,
    WARNING,
    DANGER,
};

class BMEReader {
    private:
    Adafruit_BME280& bme;
    BMEData data;

    public:
    BMEReader(Adafruit_BME280& bme280) : bme(bme280) {
    }

    [[nodiscard]] bool updateData() noexcept {
        float temp = bme.readTemperature();
        float hum  = bme.readHumidity();
        float pres = bme.readPressure() / 100.0F;


        // ===== Invalid lecture =====
        if (isnan(temp) || isnan(hum) || isnan(pres)) {
            data.isValidRead = false;
            return false;
        }

        // ===== READING DATA =====
        data.temp = bme.readTemperature();
        data.humid = bme.readHumidity();
        data.press = bme.readPressure() / 100.f;
        data.altitude = bme.readAltitude(SEALEVELPRESSURE_HPA);
        
        // ===== Valid lecture =====
        data.isValidRead = true;
        return true;
    }

    String toString() const {
        String s = "Temperature: " + String(data.temp) + " °C, ";
        s += "Humidity: " + String(data.humid) + " %, ";
        s += "Pressure: " + String(data.press) + " hPa";
        return s;
    }

    const BMEData& getData() const {return data;}
    const bool& getStatus() const {return data.isValidRead; }
};







#endif