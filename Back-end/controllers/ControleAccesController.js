const ControleAcces = require("../models/ControleAcces");
const User = require("../models/User");
const { validationResult } = require("express-validator");
const moment = require("moment");

// Enregistrer un pointage Check-In ou Check-Out
exports.storeControleAcces = async(req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
    }

    const { userId, statut } = req.body;

    try {
        const user = await User.findById(userId);
        if (!user) {
            return res.status(404).json({ message: "Utilisateur non trouvé" });
        }

        const userInfo = {
            matricule: user.matricule || "Non défini",
            nom: user.nom || "Non défini",
            prenom: user.prenom || "Non défini",
            statut: user.status || "Actif",
        };

        // Exemple avec moment.js
        const currentDate = moment(); // Utilisation de moment() pour obtenir la date actuelle


        const hourRecorded = currentDate.format("HH:mm");
        const entryTime = moment("09:00", "HH:mm");
        const exitTime = moment("17:00", "HH:mm");

        let status = "Présent";
        if (currentDate.isAfter(exitTime)) {
            status = "Absent";
        } else if (currentDate.isAfter(entryTime)) {
            status = "Retard";
        }

        const existingCheckIn = await ControleAcces.findOne({
            userId,
            date: currentDate.format("YYYY-MM-DD"),
            type: "Check-In",
        });

        if (existingCheckIn) {
            const existingCheckOut = await ControleAcces.findOne({
                userId,
                date: currentDate.format("YYYY-MM-DD"),
                type: "Check-Out",
            });

            if (existingCheckOut) {
                existingCheckOut.heure = hourRecorded;
                existingCheckOut.statut = statut || "En attente";
                existingCheckOut.etat = status;
                await existingCheckOut.save();
                return res.status(200).json({
                    message: "Check-Out mis à jour avec succès!",
                    controleAcces: existingCheckOut,
                    userInfo,
                });
            } else {
                const newCheckOut = new ControleAcces({
                    userId,
                    date: currentDate.format("YYYY-MM-DD"),
                    heure: hourRecorded,
                    type: "Check-Out",
                    statut: statut || "En attente",
                    heureEntreePrevue: "09:00",
                    heureDescentePrevue: "17:00",
                    etat: status,
                });
                await newCheckOut.save();
                return res.status(201).json({
                    message: "Check-Out enregistré avec succès!",
                    controleAcces: newCheckOut,
                    userInfo,
                });
            }
        } else {
            const newCheckIn = new ControleAcces({
                userId,
                date: currentDate.format("YYYY-MM-DD"),
                heure: hourRecorded,
                type: "Check-In",
                statut: statut || "En attente",
                heureEntreePrevue: "09:00",
                heureDescentePrevue: "17:00",
                etat: status,
            });
            await newCheckIn.save();
            return res.status(201).json({
                message: "Check-In enregistré avec succès!",
                controleAcces: newCheckIn,
                userInfo,
            });
        }
    } catch (error) {
        return res.status(500).json({
            message: "Erreur lors de l'enregistrement du pointage",
            error: error.message,
        });
    }
};

// Récupérer tous les pointages d'un utilisateur
exports.getControleAccesByUserId = async(req, res) => {
    try {
        const controlesAcces = await ControleAcces.find({
            userId: req.params.userId,
        });
        if (!controlesAcces || controlesAcces.length === 0) {
            return res
                .status(404)
                .json({
                    message: "Aucun pointage trouvé pour cet utilisateur",
                });
        }
        return res.status(200).json(controlesAcces);
    } catch (error) {
        return res
            .status(500)
            .json({
                message: "Erreur lors de la récupération des pointages",
                error: error.message,
            });
    }
};