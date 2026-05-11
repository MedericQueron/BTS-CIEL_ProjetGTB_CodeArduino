#include "../include/CESP32.h"
#include <Arduino.h>

CESP32::CESP32(string ssid, string password) : _ssid(ssid), _password(password), _isConnected(false){}

CESP32::~CESP32() {}

void CESP32::initialiser()
{
    // On force le mode station pour que l'ESP32 se connecte à un point d'accès
    this->connecter();
}

bool CESP32::connecter()
{
    // WiFi.begin attend des char* (C-string), d'où l'usage de c_str()
    WiFi.begin(_ssid.c_str(), _password.c_str());

    int tentatives = 0;
    // On attend la connexion pendant 10 secondes maximum (20 * 500ms)
    while (WiFi.status() != WL_CONNECTED && tentatives < 20) {
        delay(500);
        Serial.print(".");
        tentatives++;
    }

    if (WiFi.status() == WL_CONNECTED) {
        delay(2000);
        _isConnected = true;
        Serial.println("\nWiFi connecte !");
        Serial.print("Adresse IP : ");
        Serial.println(WiFi.localIP());
        return true;
    } else {
        _isConnected = false;
        Serial.println("\nEchec de la connexion WiFi.");
        return false;
    }
}

bool CESP32::verifierConnexion()
{
    _isConnected = (WiFi.status() == WL_CONNECTED);
    return _isConnected;
}

string CESP32::getIP() const
{
    if (WiFi.status() == WL_CONNECTED) {
        return string(WiFi.localIP().toString().c_str());
    }
    return "0.0.0.0";
}