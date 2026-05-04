#include "../include/CAirQuality.h"
#include "SCD30.h"

CAirQuality::CAirQuality(int id, int pin) : CCapteurArduino(id, pin), _temperature(0.0f), _humidity(0.0f), _CO2(0.0f)
{
}

CAirQuality::~CAirQuality() {}

void CAirQuality::initialiser()
{
	Wire.begin();
	Wire.setClock(5000);
	delay(2000);
	
	// Test de présence sur l'adresse 0x61 (adresse par défaut du SCD30)
    Wire.beginTransmission(0x61);
    byte error = Wire.endTransmission();

    if (error == 0) {
        Serial.println("SCD30 : Communication I2C établie !");
        scd30.initialize();
    } else {
        Serial.print("SCD30 : Erreur I2C code ");
        Serial.println(error);
        Serial.println("Vérifie ton câble Grove sur le port I2C.");
    }
}

void CAirQuality::getValues()
{
	if (scd30.isAvailable()) {
		scd30.getCarbonDioxideConcentration(_value);
		_CO2 = _value[0];
		_temperature = _value[1];
		_humidity = _value[2];
	}
}

float CAirQuality::lireTemperature() const
{
		return _temperature;
}

float CAirQuality::lireHumidity() const
{
	return _humidity;
}

float CAirQuality::lireCO2() const
{
	return _CO2;
}
