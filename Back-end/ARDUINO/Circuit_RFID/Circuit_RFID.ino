#include <SPI.h>
#include <MFRC522.h>
#include <Servo.h>

// Définition des broches
#define RST_PIN 9
#define SS_PIN 53
#define GREEN_LED_PIN 4
#define RED_LED_PIN 5
#define BUZZER_PIN 6
#define BUTTON_PIN 8
#define SERVO_PIN 7

MFRC522 mfrc522(SS_PIN, RST_PIN); // Initialisation du lecteur RFID
Servo servo; // Objet pour contrôler le servomoteur

// UID de la carte autorisée (ID spécifique)
String authorizedUID = "96a257f8"; // UID de la carte autorisée

void setup() {
  // Initialisation des pins
  pinMode(GREEN_LED_PIN, OUTPUT);
  pinMode(RED_LED_PIN, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(BUTTON_PIN, INPUT_PULLUP);
  servo.attach(SERVO_PIN); // Attache le servomoteur à la broche 7
  
  Serial.begin(9600);  // Initialisation de la communication série
  SPI.begin();         // Initialisation de la communication SPI
  mfrc522.PCD_Init();  // Initialisation du lecteur RFID
  servo.write(0);      // Fermeture de la porte au début
  
  Serial.println("Approchez la carte RFID du lecteur");
}

void loop() {
  // Vérifie si une carte RFID est présente
  if (mfrc522.PICC_IsNewCardPresent()) {
    // Si une carte est détectée, on la lit
    if (mfrc522.PICC_ReadCardSerial()) {
      String cardUID = getUID(); // Récupère l'UID de la carte lue

      // Affichage de l'UID de la carte dans le moniteur série pour débogage
      Serial.print("UID de la carte lue : ");
      Serial.println(cardUID);

      // Vérifie si l'UID de la carte correspond à l'UID autorisé
      if (cardUID == authorizedUID) {
        openDoor();  // Ouvre la porte si l'UID est valide
        tone(BUZZER_PIN, 1000, 500);  // Court bip (accès autorisé)
        delay(10000); // Porte ouverte pendant 10 secondes
        closeDoor();  // Ferme la porte après 10 secondes
      } else {
        tone(BUZZER_PIN, 500, 1000);  // Long bip (accès refusé)
      }
      
      mfrc522.PICC_HaltA(); // Arrête la communication avec la carte RFID
    }
  }

  // Bouton poussoir pour ouvrir/fermer la porte manuellement (optionnel)
  if (digitalRead(BUTTON_PIN) == LOW) {
    toggleDoor();
    delay(200); // Anti rebond
  }
}

String getUID() {
  String uid = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    uid += String(mfrc522.uid.uidByte[i], HEX);  // Conversion de chaque octet de l'UID en hexadécimal
  }
  uid.toLowerCase(); // Pour s'assurer que la comparaison est insensible à la casse
  return uid;
}

void openDoor() {
  servo.write(90); // Ouvre la porte (angle du servomoteur)
}

void closeDoor() {
  servo.write(0); // Ferme la porte
}

void toggleDoor() {
  static bool doorState = false;
  if (doorState) {
    closeDoor();
  } else {
    openDoor();
  }
  doorState = !doorState;
}
