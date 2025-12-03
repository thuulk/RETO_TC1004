#ifndef INCLUDE_CSS811READER_H
#define INCLUDE_CSS811READER_H
#define CCS_SCL D3
#define CCS_SDA D4
#include <Adafruit_CSS811.h>
#include "bmeAnalyzer.h"


struct CCSData {
    uint16_t co2  = 0;
    uint16_t tvoc = 0;
    bool isValidRead = false;
};

class CCSReader {

    private:
    Adafruit_CCS811& css811;
    CCSData data;

    public:

    explicit CCSReader(Adafruit_CCS811& sensor) : css811(sensor) {}
    [[nodiscard]] bool updateData(const BMEReader& bme) {
        
        if (!bme.getStatus()) {
            Serial.println("error al leer datos del BME");
            data.isValidRead = false;
            return false;
        }
        
        const BMEData& bmeData = bme.getData();  // <-- cámbialo si tu BMEReader es distinto
        const float& temp = bmeData.temp;
        const float& hum  = bmeData.humid;
        ccs811.setEnvironmentalData(hum, temp);

        if (!css811.available()) {
            Serial.println("css no disponible");
            data.isValidRead = false;
            return false;
        }

        if (css811.readData()) {
            Serial.println("lecutras CCS fallidas");
            data.isValidRead = false;
            return false
        }

        data.co2 = ccs811.getCO2();
        data.tvoc = ccs811.getTVOC();
        data.isValidRead = true;
        return true;
    }

    const CCSData& getData() const { return data; }
    [[nodiscard]] bool getStatus() const { return data.isValidRead; }

    String toString() const {
        String s = "CO2: "   + String(data.co2) + " ppm, "; 
        s += "TVOC: "      + String(data.tvoc) + " ppb";
        return s;
    }
 
}

#endif