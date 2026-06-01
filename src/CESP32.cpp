#include "../include/CESP32.h"
#include <Arduino.h>

CESP32::CESP32(string ssid, string password) : _ssid(ssid), _password(password), _isConnected(false){}

CESP32::~CESP32() {}

bool CESP32::initialiser()
{
    return this->connecter();
}

bool CESP32::connecter()
{
    // WiFi.begin attend des char* (C-string), d'où l'usage de c_str()
    WiFi.begin(_ssid.c_str(), _password.c_str());

    int tentatives = 0;
    while (WiFi.status() != WL_CONNECTED && tentatives < 20) {
        delay(500);
        tentatives++;
    }

    if (WiFi.status() == WL_CONNECTED) {
        delay(3000);
        _isConnected = true;
        return true;
    } else {
        _isConnected = false;
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