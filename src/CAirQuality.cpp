#include "../include/CAirQuality.h"


CAirQuality::CAirQuality(int id, string pin) : CCapteurArduino(id, pin), _temperature(0.0f), _humidity(0.0f), _CO2(0.0f)
{}

CAirQuality::~CAirQuality() {}

void CAirQuality::initialiser() // Initialise le capteur SCD30 et configure les paramètres de mesure
{
	Wire.begin();
	Wire.setClock(5000);
	delay(2000);
	
	scd30.initialize();
	scd30.setAutoSelfCalibration(true);
	scd30.setMeasurementInterval(2);
}

bool CAirQuality::getValues() // Lit les données du capteur SCD30 et les stocke dans les attributs correspondants
{
	if (scd30.isAvailable()) {
		scd30.getCarbonDioxideConcentration(_value);
		if (_value[0] <= 0 || _value[1] <= 0 || _value[2] <= 0) {
			return false; 
		}
		_CO2 = _value[0];
		_temperature = _value[1];
		_humidity = _value[2];
		return true;
	}
	return false;
}

float CAirQuality::lireTemperature() const // Retourne la température mesurée par le capteur SCD30
{
	return _temperature;
}

float CAirQuality::lireHumidity() const // Retourne l'humidité mesurée par le capteur SCD30
{
	return _humidity;
}

float CAirQuality::lireCO2() const // Retourne la concentration de CO2 mesurée par le capteur SCD30
{
	return _CO2;
}
