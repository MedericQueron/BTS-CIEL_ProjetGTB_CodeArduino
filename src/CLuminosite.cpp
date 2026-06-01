#include "../include/CLuminosite.h"

CLuminosite::CLuminosite(int id, string pin) : CCapteurArduino(id, pin)
{}

CLuminosite::~CLuminosite() {}

void CLuminosite::initialiser() // Initialise le capteur de luminosité en définissant le mode du pin et en établissant une connexion fictive
{
    CCapteurArduino::initialiser();
}


bool CLuminosite::getValue() // Lit la valeur du capteur de luminosité en effectuant une lecture analogique sur le pin spécifié et stocke la valeur dans l'attribut correspondant
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

float CLuminosite::lireLuminosite() const // Retourne la valeur de luminosité mesurée par le capteur
{
    return _value[0]; 
}