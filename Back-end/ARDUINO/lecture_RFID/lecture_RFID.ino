#include <SPI.h>
#include <MFRC522.h>

// Définir les broches utilisées pour le module RC522
#define SS_PIN 53
#define RST_PIN 9
MFRC522 rfid(SS_PIN, RST_PIN); // Instance de l'objet RFID

// Initialisation
void setup() {
  Serial.begin(9600);        // Démarrer la communication série
  SPI.begin();               // Initialiser le bus SPI
  rfid.PCD_Init();           // Initialiser le module RC522

  Serial.println("Scanner RFID prêt. Approchez une carte...");
}

// Boucle principale
void loop() {
  // Vérifier si une carte est présente
  if (!rfid.PICC_IsNewCardPresent()) return;
  if (!rfid.PICC_ReadCardSerial()) return;

  // Afficher l'UID de la carte
  Serial.print("Carte détectée avec UID : ");
  for (byte i = 0; i < rfid.uid.size; i++) {
    Serial.print(rfid.uid.uidByte[i], HEX);
    Serial.print(" ");
  }
  Serial.println();

  // Identifier et afficher le type de carte
  MFRC522::PICC_Type piccType = rfid.PICC_GetType(rfid.uid.sak);
  Serial.print("Type de carte : ");
  Serial.println(rfid.PICC_GetTypeName(piccType));

  // Construire l'UID en format texte
  String cardId = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    cardId += String(rfid.uid.uidByte[i], HEX);
  }
  Serial.print("{\"type\":\"cardRead\",\"cardId\":\"");
  Serial.print(cardId);
  Serial.println("\"}");

  // Arrêter la communication avec la carte
  rfid.PICC_HaltA();
}
