#ifndef CARDUINO_H
#define CARDUINO_H

#include "CAirQuality.h"
#include "CLuminosite.h"
#include "CESP32.h"
#include "CAffichage.h"
#include "CHTTP.h"

class CArduino
{
private:
    int _id;
    bool _isConnected;
    CAirQuality _airQualitySensor;
    CLuminosite _lightSensor;
    CESP32 _wifi;
    CAffichage _screen;
    CHTTP _httpClient;

public:
    CArduino(int id, const CAirQuality& airQualitySensor, const CLuminosite& lightSensor, const CESP32& wifi, const CAffichage& screen, const CHTTP&  httpClient);
    ~CArduino();

    void initialiser();
    void connexion();

    void lireCapteurs();
    void afficherDonnees();
    bool envoyerDonnees() const;

    int getId() const;
    bool getIsConnected() const;
    float getTemperature() const;
    float getHumidity() const;
    float getCO2() const;
    float getLuminosite() const;
};

#endif