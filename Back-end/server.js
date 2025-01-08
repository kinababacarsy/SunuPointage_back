import express from "express";
import http from "http";
import { Server as socketIo } from "socket.io";
import { SerialPort } from "serialport";
import { ReadlineParser } from "@serialport/parser-readline";
import mongoose from "mongoose";
import User from "./models/User.js";
import ControleAcces from "./models/ControleAcces.js";
import cors from "cors";

// MongoDB connection
mongoose
    .connect("mongodb://localhost:27017/pointage_API")
    .then(() => console.log("Connected to MongoDB database"))
    .catch((err) => console.error("MongoDB connection error:", err));

const app = express();
const server = http.createServer(app);

// Configuration CORS pour Socket.IO
const io = new socketIo(server, {
    cors: {
        origin: "http://localhost:4200",
        methods: ["GET", "POST"],
        credentials: true,
    },
});

// Serial Port configuration
const port = new SerialPort({
    path: "COM4", // Remplacez par le port série de votre Arduino
    baudRate: 9600,
    autoOpen: false,
});

// Configuration CORS pour Express
app.use(
    cors({
        origin: "http://localhost:4200", // Autoriser les requêtes depuis Angular
        methods: ["GET", "POST", "PUT", "DELETE"], // Méthodes autorisées
        credentials: true, // Autoriser les cookies et les en-têtes d'authentification
    })
);

// Create parser instance using ReadlineParser
const parser = port.pipe(new ReadlineParser({ delimiter: "\r\n" }));

// Gestion des erreurs du port
port.on("error", (err) => {
    console.error("Erreur port série:", err.message);
});

// Ouvrir le port manuellement
port.open((err) => {
    if (err) {
        console.error("Erreur ouverture port:", err.message);
        return;
    }
    console.log("Port série ouvert avec succès");
});

// Gestion des données Arduino avec détection RFID améliorée
parser.on("data", async (data) => {
    console.log("Arduino data:", data);

    if (data.includes("UID de la carte lue :")) {
        const uid = data.split(":")[1].trim().toLowerCase();
        console.log("Card UID detected:", uid);

        try {
            const user = await User.findOne({ cardID: uid });
            if (user) {
                console.log("Utilisateur trouvé :");
                console.log(`Matricule: ${user.matricule}`);
                console.log(`Nom: ${user.nom}`);
                console.log(`Prénom: ${user.prenom}`);
                console.log(`Email: ${user.email}`);
                console.log(`Téléphone: ${user.telephone}`);
                console.log(`Statut: ${user.status}`);
                console.log(`Cohorte ID: ${user.cohorte_id}`);
                console.log(`Département ID: ${user.departement_id}`);
                console.log(`Photo: ${user.photo}`);

                io.emit("rfid-card", {
                    type: "card-data",
                    found: true,
                    userData: {
                        matricule: user.matricule,
                        nom: user.nom,
                        prenom: user.prenom,
                        statut: user.status,
                        cardID: user.cardID,
                        photo: user.photo,
                    },
                });

                const currentDate = new Date();
                const dateStr = currentDate.toISOString().split("T")[0];
                const hourStr = currentDate
                    .toISOString()
                    .split("T")[1]
                    .substring(0, 5);

                const existingCheckIn = await ControleAcces.findOne({
                    userId: user.id,
                    date: dateStr,
                    type: "Check-In",
                });

                if (!existingCheckIn) {
                    const controleCheckIn = new ControleAcces({
                        userId: user.id,
                        cardID: user.cardID, // Ajouter le cardID ici
                        date: dateStr,
                        heure: hourStr, // Ajouter l'heure ici
                        type: "Check-In",
                        statut: "En attente",
                        heureEntreePrevue: "09:00",
                        heureDescentePrevue: "17:00",
                        etat: "Présent",
                    });
                    await controleCheckIn.save();

                    console.log("Check-In enregistré pour:", user.matricule);
                    console.log("Date Check-In:", controleCheckIn.date);
                    console.log("Heure Check-In:", controleCheckIn.heure);
                    console.log("Statut Check-In:", controleCheckIn.statut);
                    console.log("État Check-In:", controleCheckIn.etat);

                    const controleCheckOut = new ControleAcces({
                        userId: user.id,
                        cardID: user.cardID, // Ajouter le cardID ici
                        date: dateStr,
                        heure: "Non défini", // Ajouter l'heure ici
                        type: "Check-Out",
                        statut: "En attente",
                        heureEntreePrevue: "09:00",
                        heureDescentePrevue: "17:00",
                        etat: "Absent",
                    });
                    await controleCheckOut.save();

                    console.log("Check-Out enregistré pour:", user.matricule);
                    console.log("Date Check-Out:", controleCheckOut.date);
                    console.log("Heure Check-Out:", controleCheckOut.heure);
                    console.log("Statut Check-Out:", controleCheckOut.statut);
                    console.log("État Check-Out:", controleCheckOut.etat);

                    io.emit("Check-In", {
                        type: "Check-In",
                        date: controleCheckIn.date,
                        heure: controleCheckIn.heure,
                        userId: controleCheckIn.userId,
                        timestamp: new Date().toISOString(),
                    });

                    console.log("Check-In émis avec succès.");
                } else {
                    console.log(
                        "Check-In déjà enregistré pour cet utilisateur aujourd'hui"
                    );
                    console.log("Check-In existant :");
                    console.log("Date Check-In:", existingCheckIn.date);
                    console.log("Heure Check-In:", existingCheckIn.heure);
                    console.log("Statut Check-In:", existingCheckIn.statut);
                    console.log("État Check-In:", existingCheckIn.etat);

                    const existingCheckOut = await ControleAcces.findOne({
                        userId: user.id,
                        date: dateStr,
                        type: "Check-Out",
                    });

                    if (existingCheckOut) {
                        existingCheckOut.heure = hourStr; // Mettre à jour l'heure ici
                        existingCheckOut.statut = "En attente";
                        existingCheckOut.etat =
                            hourStr < "17:00" ? "Présent" : "Absent";
                        await existingCheckOut.save();

                        console.log(
                            "Check-Out mis à jour pour:",
                            user.matricule
                        );
                        console.log("Date Check-Out:", existingCheckOut.date);
                        console.log("Heure Check-Out:", existingCheckOut.heure);
                        console.log(
                            "Statut Check-Out:",
                            existingCheckOut.statut
                        );
                        console.log("État Check-Out:", existingCheckOut.etat);

                        io.emit("checkout-status", {
                            type: "checkout-status",
                            status: "success",
                            userData: {
                                matricule: user.matricule,
                                nom: user.nom,
                                prenom: user.prenom,
                                photo: user.photo,
                            },
                        });
                    } else {
                        console.log("Aucun Check-Out trouvé pour aujourd'hui.");
                        const controleCheckOut = new ControleAcces({
                            userId: user.id,
                            cardID: user.cardID, // Ajouter le cardID ici
                            date: dateStr,
                            heure: hourStr, // Ajouter l'heure ici
                            type: "Check-Out",
                            statut: "En attente",
                            heureEntreePrevue: "09:00",
                            heureDescentePrevue: "17:00",
                            etat: "Absent",
                        });
                        await controleCheckOut.save();

                        console.log(
                            "Check-Out enregistré pour:",
                            user.matricule
                        );
                        console.log("Date Check-Out:", controleCheckOut.date);
                        console.log("Heure Check-Out:", controleCheckOut.heure);
                        console.log(
                            "Statut Check-Out:",
                            controleCheckOut.statut
                        );
                        console.log("État Check-Out:", controleCheckOut.etat);

                        io.emit("Check-Out", {
                            type: "Check-Out",
                            date: controleCheckOut.date,
                            heure: controleCheckOut.heure,
                            userId: controleCheckOut.userId,
                            timestamp: new Date().toISOString(),
                        });

                        console.log("Nouveau Check-Out émis avec succès.");
                    }
                }
            } else {
                console.log("Utilisateur non trouvé");
                io.emit("rfid-card", {
                    type: "card-data",
                    found: false,
                    message: "Utilisateur non trouvé",
                });
            }
        } catch (error) {
            console.error("Erreur lors de la recherche utilisateur:", error);
            io.emit("rfid-card", {
                type: "card-data",
                found: false,
                message: "Erreur lors de la recherche",
            });
        }
    }
});
// Route pour récupérer les pointages par cardID
app.get("/api/controle-acces/pointages/:cardID", async (req, res) => {
    try {
        const cardID = req.params.cardID;
        const pointages = await ControleAcces.find({ cardID });
        if (!pointages || pointages.length === 0) {
            return res
                .status(404)
                .json({ message: "Aucun pointage trouvé pour cette carte" });
        }
        return res.status(200).json(pointages);
    } catch (error) {
        return res.status(500).json({
            message: "Erreur lors de la récupération des pointages",
            error: error.message,
        });
    }
});

// Route pour créer un contrôle d'accès
app.post("/api/controle-acces", async (req, res) => {
    const { userId, cardID, date, heure, type, statut, etat } = req.body;

    try {
        const newControleAcces = new ControleAcces({
            userId,
            cardID,
            date,
            heure,
            type,
            statut,
            etat,
        });
        await newControleAcces.save();
        return res.status(201).json({
            message: "Contrôle d'accès enregistré avec succès!",
            controleAcces: newControleAcces,
        });
    } catch (error) {
        return res.status(500).json({
            message: "Erreur lors de l'enregistrement du contrôle d'accès",
            error: error.message,
        });
    }
});
// WebSocket connection handling
io.on("connection", (socket) => {
    console.log("A user connected");

    socket.on("disconnect", () => {
        console.log("User disconnected");
    });
});

// Vérification périodique de la connexion au port série
setInterval(() => {
    if (!port.isOpen) {
        console.log("Port déconnecté, tentative de reconnexion...");
        port.open();
    }
}, 5000);

// Start server
server.listen(3000, () => {
    console.log("WebSocket server listening on port 3000");
});
