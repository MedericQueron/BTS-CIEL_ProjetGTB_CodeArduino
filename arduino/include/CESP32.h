#ifndef CESP32_H
#define CESP32_H

#include <Arduino.h>
#include <WiFiS3.h>
#include <iostream>

using namespace std;

class CESP32
{
private:
    string _ssid;
    string _password;
    bool _isConnected;

public:
    CESP32(string ssid, string password);
    ~CESP32();

    bool initialiser();
    bool connecter();
    bool verifierConnexion();
    string getIP() const;
};

#endif