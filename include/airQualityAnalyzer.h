#ifndef INCLUDE_AIRQUALITYANALYZER_H
#define INCLUDE_AIRQUALITYANALYZER_H
#include <stdint.h>
#include<PMserial.h>
#define PMS_RX D3
#define PMS_TX D4

// =====================
//   AIR QUALITY THRESHOLDS
//   (adjust depending on requirements)
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
    uint16_t pm1;   // PM <= 1.0 µm
    uint16_t pm25;  // PM <= 2.5 µm
    uint16_t pm10;  // PM <= 10  µm

    // ==== PARTICLE READINGS (#/100cm³) ====
    uint16_t p03;   // >= 0.3 µm
    uint16_t p05;   // >= 0.5 µm
    uint16_t p10;   // >= 1.0 µm
    uint16_t p25;   // >= 2.5 µm
    uint16_t p50;   // >= 5.0 µm
    uint16_t p100;  // >= 10  µm
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
    PMSData data;

    public: 
    PMSReader(SerialPM& pms5003) : pms(pms5003) {}


    [[nodiscard]] bool updateData() noexcept {

        pms.read();

        // ===== Invalid lecture =====
        if (!pms.has_particulate_matter() || !pms.has_number_concentration()) {
            data.isValidRead = false;
            return false;
        }

        // ==== READING DATA ====
        data.pm1  = pms.pm01;
        data.pm25 = pms.pm25; 
        data.pm10 = pms.pm10;
        data.p03  = pms.n0p3;
        data.p05  = pms.n0p5;
        data.p10  = pms.n1p0;
        data.p25  = pms.n2p5;
        data.p50  = pms.n5p0;
        data.p100 = pms.n10p0;

        // ===== Valid lecture =====
        data.isValidRead = true;
        return true;
    }

    AirQuality classifyData() const {

        // ===== CASE NO READING =====
        if (!data.isValidRead) return AirQuality::ERROR;

        // ===== Safe mass concentration checks =====
        bool pmSafe = data.pm1 <= PM1_MAX_SAFE &&
                    data.pm25 <= PM25_MAX_SAFE &&
                    data.pm10 <= PM10_MAX_SAFE;

        float smallCount = static_cast<float>(data.p03) +
                            static_cast<float>(data.p05) +
                            static_cast<float>(data.p10);

        float largeCount = static_cast<float>(data.p25) +
                            static_cast<float>(data.p50) +
                            static_cast<float>(data.p100);

        bool isCountSafe = smallCount <= SMALLCOUNT_MAX_SAFE && largeCount <=LARGECOUNT_MAX_SAFE;

        // ===== Danger mass concentration check =====
        bool pmDanger = data.pm10 > PM10_MAX_SAFE * AQI_WARNING_FACTOR ||
                        data.pm25 > PM25_MAX_SAFE * AQI_WARNING_FACTOR;
        
        bool countDanger = smallCount > SMALLCOUNT_MAX_SAFE * AQI_WARNING_FACTOR ||
                            largeCount > LARGECOUNT_MAX_SAFE * AQI_WARNING_FACTOR;

        // ===== CASES ======
        if (isCountSafe) return AirQuality::SAFE;
        else if (pmDanger || countDanger) return AirQuality::WARNING;
        else return AirQuality::WARNING;
    }

    String qualityLabel() const {
        switch (classifyData()) {
            case AirQuality::SAFE:
                return "SAFE";

            case AirQuality::WARNING:
                return "WARNING";
            
            case AirQuality::DANGER:
                return "DANGER";
            
            default:
                return "Error";
        }
    }

    String toString() const {
        String s = "PM1: " + String(data.pm1); 
        s += ", PM2.5:"     + String(data.pm25);
        s += ", PM10: "     + String(data.pm10);
        s += "| AQI: "     + qualityLabel();

        return s;
    }

    const PMSData& getData() const { return data; }

};
#endif