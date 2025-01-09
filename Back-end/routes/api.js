// routes/api.js
const express = require('express');
const router = express.Router();

// Importer les contrôleurs
const controleAccesController = require('./controllers/controleAccesController');

// Route pour enregistrer un pointage (Check-In ou Check-Out)
router.post('/controle-acces', controleAccesController.storeControleAcces);

// Route pour récupérer les pointages d'un utilisateur par son ID
router.get('/controle-acces/:userId', controleAccesController.getControleAccesByUserId);

// Exporter le routeur pour l'utiliser dans l'application principale
module.exports = router;