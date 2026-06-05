#include "../include/CArduino.h"

CArduino::CArduino(int id, const CAirQuality& airQualitySensor, const CLuminosite& lightSensor, const CESP32& wifi, const CAffichage& screen, const CHTTP& httpClient)
    : _id(id), _isConnected(false), _airQualitySensor(airQualitySensor), _lightSensor(lightSensor), _wifi(wifi), _screen(screen), _httpClient(httpClient) {}

CArduino::~CArduino() {}

void CArduino::initialiser() { // Initialise tous les composants et tente de se connecter 1 fois au WiFi
    _wifi.initialiser();
    _isConnected = _wifi.verifierConnexion();
    _airQualitySensor.initialiser();
    _lightSensor.initialiser();
    _screen.initialiser();
}

void CArduino::connexion() { // Tente de se connecter au WiFi tant que la connexion n'est pas établie
    while (_isConnected == false) 
    {
        _isConnected = _wifi.connecter();
    }    
}

void CArduino::lireCapteurs() { // Lit les données des capteurs et les stocke dans les attributs correspondants
    _airQualitySensor.getValues();
    _lightSensor.getValue();
}

void CArduino::afficherDonnees() {   // Affiche les données sur l'écran LCD
    _screen.afficherDonnees(
        _airQualitySensor.lireTemperature(), 
        _airQualitySensor.lireHumidity(), 
        _airQualitySensor.lireCO2(), 
        _lightSensor.lireLuminosite()
    );
    _screen.alerteCO2(_airQualitySensor.lireCO2());
}

void CArduino::envoyerDonnees() const { // Envoie les données au serveur via HTTP
    if (!_isConnected) {
        return;
    }

    _httpClient.envoyerDonnees(
        _airQualitySensor.lireTemperature(), 
        _airQualitySensor.lireHumidity(), 
        _airQualitySensor.lireCO2(), 
        _lightSensor.lireLuminosite()
    );
}

int CArduino::getId() const {
    return _id;
}

bool CArduino::getIsConnected() const {
    return _isConnected;
}

float CArduino::getTemperature() const {
    return (_airQualitySensor.lireTemperature());
}

float CArduino::getHumidity() const {
    return (_airQualitySensor.lireHumidity());
}

float CArduino::getCO2() const {
    return (_airQualitySensor.lireCO2());
}

float CArduino::getLuminosite() const {
    return (_lightSensor.lireLuminosite());
}