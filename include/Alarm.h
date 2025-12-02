#ifndef INCLUDE_ALARM_H
#define INCLUDE_ALARM_H

class Alarm {
    private:
        bool  isOn      = false;
        float magnitude = 0.0f; 
        float switchMin;
        float switchMax;

    public:
        Alarm(const float& sMin, const float& sMax) : switchMin(sMin), switchMax(sMax) {}

        void setMagnitude(const float& value) {
            magnitude = value;
        }

        // Recalcula el estado de la alarma
        void update() {
            // ON si estamos fuera del rango [switchMin, switchMax]
            if (magnitude >= switchMin && magnitude <= switchMax)
                isOn = false;
            else
                isOn = true;
        }

        bool getState() const {
            return isOn;
        }
};

#endif