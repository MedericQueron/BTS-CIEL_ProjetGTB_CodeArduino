#ifndef CCapteurArduino_H
#define CCapteurArduino_H
#include <iostream>

using namespace std;

class CCapteurArduino
{
protected:
	int _id;
	string _pin;
	float _value[3]; // Pour Capteur Qualité d'air, 0: temperature, 1:  humidity, 3: CO2
	bool _isConnected; // Indique si le capteur est connecté
	bool _pinmode; // Indique si le pin est en mode INPUT 


public:
	CCapteurArduino(int id, string pin);
	 ~CCapteurArduino();

	virtual void initialiser();

	int getId() const;
	string getPin() const;
	float getValue(int index);
	bool getIsConnected() const;
	bool getPinMode() const;

};

#endif // !CCapteurArduino_H
