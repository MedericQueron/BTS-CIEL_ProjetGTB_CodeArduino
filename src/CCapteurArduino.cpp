#include "../include/CCapteurArduino.h"

CCapteurArduino::CCapteurArduino(int id, string pin) : _id(id), _pin(pin), _isConnected(false), _pinmode(false)
{
	_value[0] = _value[1] = _value[2] = 0.0f;
}

CCapteurArduino::~CCapteurArduino() {}

void CCapteurArduino::initialiser()
{
	_isConnected = true;
	_pinmode = true;
}

int CCapteurArduino::getId() const
{
	return _id;
}

string CCapteurArduino::getPin() const
{
	return _pin;
}

float CCapteurArduino::getValue(int index)
{
	return _value[index];
}

bool CCapteurArduino::getIsConnected() const
{
	return _isConnected;
}

bool CCapteurArduino::getPinMode() const
{
	return _pinmode;
}
