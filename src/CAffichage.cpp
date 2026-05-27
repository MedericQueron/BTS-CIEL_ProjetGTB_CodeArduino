#include "../include/CAffichage.h"

CAffichage::CAffichage() {}

void CAffichage::initialiser() {
    _lcd.begin(16, 2);
    _lcd.setRGB(255, 255, 255); // Rétroéclairage blanc au démarrage
    _lcd.print("Systeme Pret");
    delay(1000);
}

void CAffichage::afficherDonnees(float temp, float hum, float co2, float lux) {
    _lcd.clear();
    // Ligne 1 : Température et Humidité
    _lcd.setCursor(0, 0);
    _lcd.print("T:"); _lcd.print(temp, 1);
    _lcd.setCursor(8, 0);
    _lcd.print("H:"); _lcd.print(hum, 0); _lcd.print("%");

    // Ligne 2 : Alternance ou CO2 fixe
    _lcd.setCursor(0, 1);
    _lcd.print("CO2:"); _lcd.print((int)co2);
    _lcd.setCursor(10, 1);
    _lcd.print("L:"); _lcd.print((int)lux);
}

void CAffichage::alerteCO2(float co2) {
    if (co2 > 1500) {
        _lcd.setRGB(255, 0, 0); // Rouge si air vicié
    } else if (co2 > 800) {
        _lcd.setRGB(255, 128, 0); // Orange si seuil d'alerte
    } else {
        _lcd.setRGB(0, 255, 0); // Vert si air sain
    }
}