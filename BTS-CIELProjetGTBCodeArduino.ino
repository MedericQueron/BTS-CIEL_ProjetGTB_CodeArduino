#include "include/CCapteurArduino.h"

CCapteurArduino capteur(1, 2);

void setup() {
  Serial.begin(9600);
  capteur.initialiser();

}

void loop() {
  Serial.print("Capteur ID: ");
  Serial.println(capteur.getId());
  Serial.print("Capteur Pin: ");
  Serial.println(capteur.getPin());
  delay(1000);
}