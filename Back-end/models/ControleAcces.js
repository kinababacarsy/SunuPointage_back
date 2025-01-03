import mongoose from 'mongoose';

// Schéma de contrôle d'accès
const controleAccesSchema = new mongoose.Schema({
    userId: {
        type: mongoose.Schema.Types.ObjectId,
        ref: 'User', // Référence à l'utilisateur
        required: true,
    },
    date: {
        type: Date,
        required: true,
    },
    heure: {
        type: String,
        required: true,
    },
    type: String,
    statut: {
        type: String,
        default: 'En attente',
    },
    heureEntreePrevue: String,
    heureDescentePrevue: String,
    etat: String,
});

const ControleAcces = mongoose.model('ControleAcces', controleAccesSchema);

export default ControleAcces;