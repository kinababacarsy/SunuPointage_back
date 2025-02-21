const User = require('../models/User');
const bcrypt = require('bcrypt');
const { validationResult } = require('express-validator');

// Lister tous les utilisateurs
exports.getAllUsers = async(req, res) => {
    try {
        const users = await User.find();
        return res.status(200).json(users);
    } catch (error) {
        return res.status(500).json({ message: 'Erreur lors de la récupération des utilisateurs', error: error.message });
    }
};

// Récupérer un utilisateur par ID
exports.getUserById = async(req, res) => {
    try {
        const user = await User.findById(req.params.id);
        if (!user) {
            return res.status(404).json({ message: 'Utilisateur non trouvé' });
        }
        return res.status(200).json(user);
    } catch (error) {
        return res.status(500).json({ message: 'Erreur lors de la récupération de l\'utilisateur', error: error.message });
    }
};

// Créer un utilisateur
exports.createUser = async(req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
    }

    const { nom, prenom, telephone, email, mot_de_passe, adresse, role, photo, departement_id, cohorte_id, cardID, status } = req.body;

    try {
        // Vérifier si l'utilisateur existe déjà
        const existingUser = await User.findOne({ email });
        if (existingUser) {
            return res.status(400).json({ message: 'Email déjà utilisé' });
        }

        // Hash du mot de passe
        const hashedPassword = await bcrypt.hash(mot_de_passe, 10);

        const newUser = new User({
            nom,
            prenom,
            telephone,
            email,
            mot_de_passe: hashedPassword,
            adresse,
            role,
            photo: photo || 'images/inconnu.png', // photo par défaut
            departement_id,
            cohorte_id,
            cardID,
            status: status || 'Actif',
            matricule: `${role.substring(0, 2).toUpperCase()}-${Math.floor(Math.random() * 1000)}`
        });

        await newUser.save();

        return res.status(201).json({
            message: 'Utilisateur ajouté avec succès!',
            user: newUser
        });
    } catch (error) {
        return res.status(500).json({ message: 'Échec de l\'ajout de l\'utilisateur', error: error.message });
    }
};

// Mettre à jour un utilisateur
exports.updateUser = async(req, res) => {
    const { nom, prenom, telephone, email, mot_de_passe, adresse, role, photo, departement_id, cohorte_id, cardID, status } = req.body;

    try {
        const user = await User.findById(req.params.id);
        if (!user) {
            return res.status(404).json({ message: 'Utilisateur non trouvé' });
        }

        if (mot_de_passe) {
            user.mot_de_passe = await bcrypt.hash(mot_de_passe, 10);
        }

        user.nom = nom || user.nom;
        user.prenom = prenom || user.prenom;
        user.telephone = telephone || user.telephone;
        user.email = email || user.email;
        user.adresse = adresse || user.adresse;
        user.role = role || user.role;
        user.photo = photo || user.photo;
        user.departement_id = departement_id || user.departement_id;
        user.cohorte_id = cohorte_id || user.cohorte_id;
        user.cardID = cardID || user.cardID;
        user.status = status || user.status;

        await user.save();

        return res.status(200).json({
            message: 'Utilisateur mis à jour avec succès!',
            user
        });
    } catch (error) {
        return res.status(500).json({ message: 'Erreur lors de la mise à jour de l\'utilisateur', error: error.message });
    }
};

// Supprimer un utilisateur
exports.deleteUser = async(req, res) => {
    try {
        const user = await User.findById(req.params.id);
        if (!user) {
            return res.status(404).json({ message: 'Utilisateur non trouvé' });
        }

        await user.remove();

        return res.status(200).json({ message: 'Utilisateur supprimé' });
    } catch (error) {
        return res.status(500).json({ message: 'Erreur lors de la suppression de l\'utilisateur', error: error.message });
    }
};