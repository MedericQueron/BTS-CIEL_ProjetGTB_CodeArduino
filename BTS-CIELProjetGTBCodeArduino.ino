#include "include/CArduino.h"

CAirQuality SCD_30(1, "I2C");
CLuminosite LightSensor(2, "A0");
//CESP32 Wifi_PGTB("OPPO Reno8 5G", "jean1611");
CESP32 Wifi_PGTB("WIFI-PGTB_2.4Ghz", "BtsCielGTB@2026"); // paramètre 1 = SSID du réseau WiFi, paramètre 2 = mot de passe du réseau WiFi
CAffichage ecran;
CHTTP httpClient("http://192.168.3.60","/gtb/api/mesures.php"); // URL du serveur à laquelle les données seront envoyées 

CArduino monArduino(1, SCD_30, LightSensor, Wifi_PGTB, ecran, httpClient);

void setup() {
  /*
  Initialisation des composants :
  1. Initialiser la connexion WiFi en tentant de se connecter immédiatement.
  2. Initialiser les capteurs de qualité de l'air et de luminosité.
  3. Initialiser l'écran LCD et afficher un message de bienvenue.
  */
  Serial.begin(9600);
  while(!Serial);
  monArduino.initialiser();
}

void loop() { 
  /*
  1. Lire les capteurs de qualité de l'air et de luminosité et stocker les valeurs dans les attributs correspondants.
  2. Afficher les données sur l'écran LCD.
  3. Vérifier la connexion WiFi et tenter de se connecter si elle n'est pas établie.
  4. Envoyer les données au serveur via HTTP POST.
  */

  monArduino.lireCapteurs();
  monArduino.afficherDonnees();
  Serial.println("affichage good");
  
  while (monArduino.getIsConnected() == false)
  {
    Serial.println("Tentative de Connexion...");
    monArduino.connexion();
    Serial.println("Connexion échoué");
  }
  Serial.println("Wifi good");

  if (monArduino.envoyerDonnees()) {
    Serial.println("Données envoyées avec succès");
  } 
  
  
  //delay (600000); // Attendre 10 minutes avant de lire à nouveau les capteurs et d'envoyer les données au serveur
  delay(2000);
}