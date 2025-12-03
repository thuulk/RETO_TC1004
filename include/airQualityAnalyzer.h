#ifndef INCLUDE_AIRQUALITYANALYZER_H
#define INCLUDE_AIRQUALITYANALYZER_H

#include <Arduino.h>      // Para Serial, String
#include <stdint.h>
#include <PMserial.h>

// =====================
//   PINES PMS5003
// =====================
// Convención: PMS_RX = pin donde el ESP *RECIBE* (va al TXD del sensor)
//             PMS_TX = pin donde el ESP *ENVÍA*  (va al RXD del sensor)
#define PMS_RX D5   // conectado a TXD del PMS
#define PMS_TX D6   // conectado a RXD del PMS

// =====================
//   AIR QUALITY THRESHOLDS
// =====================

// "Good" thresholds for cold storage environments (µg/m³)
constexpr float PM1_MAX_SAFE       = 5.0f;    // µg/m³
constexpr float PM25_MAX_SAFE      = 5.0f;    // µg/m³
constexpr float PM10_MAX_SAFE      = 15.0f;   // µg/m³

// Particle count per volume (#/100 cm³ approx.)
constexpr float SMALLCOUNT_MAX_SAFE = 3000.0f; // p03 + p05 + p10c
constexpr float LARGECOUNT_MAX_SAFE = 500.0f;  // p25 + p50 + p100

// Multiplier to distinguish WARNING vs BAD conditions
constexpr float AQI_WARNING_FACTOR  = 3.0f; // 3x above "good" threshold

// ===== DATA STRUCTURE ======
struct PMSData {

    // ==== READING STATUS ====
    bool isValidRead = false;

    // ==== PM en µg/m³ ====
    uint16_t pm1  = 0;  // PM <= 1.0 µm
};

enum class AirQuality {
    ERROR,
    SAFE,
    WARNING,
    DANGER,
};

class PMSReader {
private:
    SerialPM& pms;
    PMSData   data;

public:
    PMSReader(SerialPM& pms5003) : pms(pms5003) {}

    [[nodiscard]] bool updateData() noexcept {

        // Lee del sensor
        pms.read();

        // Debug bruto de la librería
        if (pms) {
            Serial.println(F("[PMS] VALID FRAME RECEIVED"));
            Serial.print(F("  PM1: "));  Serial.println(pms.pm01);
            Serial.print(F("  PM2.5: "));Serial.println(pms.pm25);
            Serial.print(F("  PM10: ")); Serial.println(pms.pm10);
        } else {
            Serial.print(F("[PMS] INVALID FRAME, status = "));
            Serial.println(pms.status);  // 1 = timeout, 2 = checksum, 3 = header...
        }

        // Si no hay datos de masa, no hay nada que hacer
        if (!pms.has_particulate_matter()) {
            data.isValidRead = false;
            return false;
        }

        // ==== MASS CONCENTRATION (µg/m³) ====
        data.pm1  = pms.pm01;

        data.isValidRead = true;
        return true;
    }

    AirQuality classifyData() const {

        if (!data.isValidRead) return AirQuality::ERROR;

        bool pmSafe = data.pm1  <= PM1_MAX_SAFE &&
                      data.pm25 <= PM25_MAX_SAFE &&
                      data.pm10 <= PM10_MAX_SAFE;

        float smallCount = static_cast<float>(data.p03) +
                           static_cast<float>(data.p05) +
                           static_cast<float>(data.p10);

        float largeCount = static_cast<float>(data.p25) +
                           static_cast<float>(data.p50) +
                           static_cast<float>(data.p100);

        bool isCountSafe = smallCount <= SMALLCOUNT_MAX_SAFE &&
                           largeCount <= LARGECOUNT_MAX_SAFE;

        bool pmDanger = data.pm10 > PM10_MAX_SAFE  * AQI_WARNING_FACTOR ||
                        data.pm25 > PM25_MAX_SAFE * AQI_WARNING_FACTOR;
        
        bool countDanger = smallCount > SMALLCOUNT_MAX_SAFE * AQI_WARNING_FACTOR ||
                           largeCount > LARGECOUNT_MAX_SAFE * AQI_WARNING_FACTOR;

        if (pmSafe && isCountSafe)          return AirQuality::SAFE;
        else if (pmDanger || countDanger)   return AirQuality::DANGER;
        else                                return AirQuality::WARNING;
    }

    String qualityLabel() const {
        switch (classifyData()) {
            case AirQuality::SAFE:    return "SAFE";
            case AirQuality::WARNING: return "WARNING";
            case AirQuality::DANGER:  return "DANGER";
            default:                  return "ERROR";
        }
    }

    String toString() const {
        String s = "PM1: "   + String(data.pm1); 
        s += ", PM2.5:"      + String(data.pm25);
        s += ", PM10:"       + String(data.pm10);
        s += " | AQI: "      + qualityLabel();
        return s;
    }

    const PMSData& getData() const { return data; }
    const bool& getStatus() const { return data.isValidRead; }
};

#endif // INCLUDE_AIRQUALITYANALYZER_H
