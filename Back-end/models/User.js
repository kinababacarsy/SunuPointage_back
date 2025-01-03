import mongoose from 'mongoose';
import bcrypt from 'bcryptjs'; // Si tu veux gérer le cryptage des mots de passe (optionnel)
import jwt from 'jsonwebtoken'; // Pour gérer le JWT

// Schéma de l'utilisateur
const userSchema = new mongoose.Schema({
    matricule: {
        type: String,
        required: true,
    },
    nom: {
        type: String,
        required: true,
    },
    prenom: {
        type: String,
        required: true,
    },
    email: {
        type: String,
        required: true,
        unique: true,
    },
    mot_de_passe: {
        type: String,
        required: true,
    },
    telephone: String,
    adresse: String,
    photo: String,
    role: String,
    departement_id: String,
    cohorte_id: String,
    cardID: {
        type: String,
        unique: true, // cardID unique pour l'utilisateur
        required: true,
    },
    status: {
        type: String,
        default: 'actif',
    },
});

// Méthode pour obtenir l'ID du JWT
userSchema.methods.getJWTIdentifier = function() {
    return this._id; // Retourne l'ID de l'utilisateur (par défaut _id dans MongoDB)
};

// Méthode pour récupérer les claims supplémentaires dans le JWT
userSchema.methods.getJWTCustomClaims = function() {
    return {}; // Ajoute des informations supplémentaires si nécessaire
};

// Hash le mot de passe avant de sauvegarder
userSchema.pre('save', async function(next) {
    if (this.isModified('mot_de_passe')) {
        const salt = await bcrypt.genSalt(10);
        this.mot_de_passe = await bcrypt.hash(this.mot_de_passe, salt);
    }
    next();
});

const User = mongoose.model('User', userSchema);

export default User;