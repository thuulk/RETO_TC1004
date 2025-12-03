#ifndef INCLUDE_AIRQUALITYANALYZER_H
#define INCLUDE_AIRQUALITYANALYZER_H

#include <Arduino.h>      // Para Serial, String, boolean
#include <stdint.h>
#include "PMS5003.h"      // define struct pms5003data y readPMSdata(Stream *s)

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
    bool     isValidRead = false;

    // ==== PM en µg/m³ ====
    uint16_t pm1         = 0;  // PM <= 1.0 µm (mapeado desde pm10_env del PMS5003)
};

// El .h de PMS5003 define un *global*:
//   struct pms5003data data;
// Lo declaramos como externo para poder leerlo aquí sin redefinirlo.
extern struct pms5003data data;

class PMSReader {
private:
    // Usamos una referencia genérica a Stream para que funcione con SoftwareSerial,
    // HardwareSerial, etc. (SoftwareSerial hereda de Stream).
    Stream&  pms;
    PMSData  dataLocal;

public:
    // Constructor: recibe el puerto serie que ya creaste (ej. SoftwareSerial pmsSerial)
    PMSReader(Stream& pms5003) : pms(pms5003) {}

    // Llama a esto periódicamente para actualizar la lectura del sensor.
    [[nodiscard]] bool updateData() noexcept {

        // readPMSdata viene de PMS5003.h y llena el struct global ::data
        // Devuelve true (boolean) si se leyó un frame válido.
        if (!readPMSdata(&pms)) {
            dataLocal.isValidRead = false;
            return false;
        }

        // ==== MASS CONCENTRATION (µg/m³) ====
<<<<<<< Updated upstream
        data.pm1  = pms.pm01;
        data.pm25 = pms.pm25; 
        data.pm10 = pms.pm10;

        // ==== NUMBER CONCENTRATION (solo si está disponible) ====
        if (pms.has_number_concentration()) {
            data.p03  = pms.n0p3;
            data.p05  = pms.n0p5;
            data.p10  = pms.n1p0;
            data.p25  = pms.n2p5;
            data.p50  = pms.n5p0;
            data.p100 = pms.n10p0;
        } else {
            data.p03 = data.p05 = data.p10 = 0;
            data.p25 = data.p50 = data.p100 = 0;
        }
=======
        //
        // OJO con el naming del PMS5003:
        //  pm10_*  -> PM1.0
        //  pm25_*  -> PM2.5
        //  pm100_* -> PM10
        //
        // Aquí solo exponemos PM1.0 como ejemplo.
        dataLocal.pm1 = ::data.pm10_env;  // PM1.0 ambiental
>>>>>>> Stashed changes

        dataLocal.isValidRead = true;
        return true;
    }

    // Representación simple en texto (para Serial.print/debug)
    String toString() const {
        if (!dataLocal.isValidRead) {
            return String("PMS: lectura NO válida");
        }

        String s = "PM1: " + String(dataLocal.pm1) + " ug/m3";
        return s;
    }

    // Getters "seguros"
    const PMSData& getData() const      { return dataLocal; }
    const bool&    getStatus() const    { return dataLocal.isValidRead; }
};

#endif // INCLUDE_AIRQUALITYANALYZER_H
