#ifndef CAirQuality_H
#define CAirQuality_H

#include "CCapteurArduino.h"
#include "SCD30.h"

class CAirQuality : public CCapteurArduino
{
private:
	float _temperature;
	float _humidity;
	float _CO2;

public:
	CAirQuality(int id, int pin);
	~CAirQuality();

	void initialiser();

	void getValues();
	float lireTemperature() const;
	float lireHumidity() const;
	float lireCO2() const;

};

#endif // !CAirQuality_H
