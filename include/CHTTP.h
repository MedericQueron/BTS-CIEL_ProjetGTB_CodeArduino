#ifndef CHTTP_H
#define CHTTP_H

#include <Arduino.h>
#include <ArduinoHttpClient.h>
#include <iostream>

using namespace std;
class CHTTP {
private:

    String _serverUrl;

public:

    CHTTP(String url);

    bool envoyerDonnees(float temperature, float humidite, int co2, int luminosite) const;
};

#endif // CHTTP_H