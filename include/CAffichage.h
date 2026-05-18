#ifndef CAFFICHAGE_H
#define CAFFICHAGE_H

#include <rgb_lcd.h>
#include <string>

using namespace std;

class CAffichage {
private:
    rgb_lcd _lcd;
public:
    CAffichage();
    void initialiser();
    void afficherDonnees(float temp, float hum, float co2, float lux);
    void alerteCO2(float co2);
};

#endif