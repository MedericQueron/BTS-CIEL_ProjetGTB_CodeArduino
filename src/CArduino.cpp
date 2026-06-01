#include <../include/CArduino.h>

CArduino::CArduino(int id, const CAirQuality& airQualitySensor, const CLuminosite& lightSensor, const CESP32& wifi, const CAffichage& screen, const CHTTP& httpClient)
    : _id(id), _isConnected(false), _airQualitySensor(airQualitySensor), _lightSensor(lightSensor), _wifi(wifi), _screen(screen), _httpClient(httpClient) {}

CArduino::~CArduino() {}

void CArduino::initialiser() {
    _wifi.initialiser();
    _isConnected = _wifi.verifierConnexion();
    _airQualitySensor.initialiser();
    _lightSensor.initialiser();
    _screen.initialiser();
}

void CArduino::connexion() {
    if (_isConnected == false)
    {
        _isConnected = _wifi.connecter();
    }    
}

void CArduino::lireCapteurs() {
    _airQualitySensor.getValues();
    _lightSensor.getValue();
}

void CArduino::afficherDonnees() const {
    _screen.afficherDonnees(
        _airQualitySensor.lireTemperature(), 
        _airQualitySensor.lireHumidity(), 
        _airQualitySensor.lireCO2(), 
        _lightSensor.lireLuminosite()
    );
    _screen.alerteCO2(_airQualitySensor.lireCO2());
}

void CArduino::envoyerDonnees() const {
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