#include "../include/CLuminosite.h"

CLuminosite::CLuminosite(int id, string pin) : CCapteurArduino(id, pin)
{}

CLuminosite::~CLuminosite() {}

void CLuminosite::initialiser()
{
    CCapteurArduino::initialiser();

    
}


bool CLuminosite::getValue()
{
    int pinAnalogique;
    if (_pin == "A0") pinAnalogique = A0;
    else if (_pin == "A1") pinAnalogique = A1;
    else if (_pin == "A2") pinAnalogique = A2;
    else if (_pin == "A3") pinAnalogique = A3;
    
    else pinAnalogique = A0; 

    
    _value[0] = analogRead(pinAnalogique);
    return true;
    
}

float CLuminosite::lireLuminosite() const
{
    return _value[0]; 
}