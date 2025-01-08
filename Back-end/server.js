import express from 'express';
import http from 'http';
import { Server as socketIo } from 'socket.io';
import { SerialPort } from 'serialport';
import { ReadlineParser } from '@serialport/parser-readline';
import mongoose from 'mongoose';
import User from './models/User.js';
import ControleAcces from './models/ControleAcces.js';

// MongoDB connection
mongoose.connect('mongodb://localhost:27017/sunupointage')
    .then(() => console.log('Connected to MongoDB database'))
    .catch(err => {
        console.error('MongoDB connection error:', err);
        process.exit(1); // Quitte le processus si la connexion échoue
    });

const app = express();
const server = http.createServer(app);

// Configuration CORS pour Socket.IO
const io = new socketIo(server, {
    cors: {
        origin: "http://localhost:4200",
        methods: ["GET", "POST"],
        credentials: true
    }
});

// Serial Port configuration
const port = new SerialPort({
    path: '/dev/ttyACM0',
    baudRate: 9600,
    autoOpen: false
});

// Create parser instance using ReadlineParser
const parser = port.pipe(new ReadlineParser({ delimiter: '\r\n' }));



// Gestion des erreurs du port
port.on('error', (err) => {
    console.error('Erreur port série:', err.message);
});

// Ouvrir le port manuellement
port.open((err) => {
    if (err) {
        console.error('Erreur ouverture port:', err.message);
        return;
    }
    console.log('Port série ouvert avec succès');
});

// Gestion des données Arduino avec détection RFID améliorée
parser.on('data', async(data) => {
    console.log('Arduino data:', data);

    // Vérifier si c'est un UID de carte
    if (data.includes('UID de la carte lue :')) {
        // Extraire l'UID et le convertir en minuscule et supprimer les espaces
        const uid = data.split(':')[1].trim().toLowerCase(); // Ici on récupère l'UID
        console.log('Card UID detected:', uid);

        try {
            // Rechercher l'utilisateur dans la base de données avec l'UID converti en minuscule
            const user = await User.findOne({ cardID: uid }); // Requête MongoDB

            if (user) {
                // Si l'utilisateur est trouvé, afficher ses informations dans le terminal
                console.log('Utilisateur trouvé :');
                console.log(`Matricule: ${user.matricule}`);
                console.log(`Nom: ${user.nom}`);
                console.log(`Prénom: ${user.prenom}`);
                console.log(`Email: ${user.email}`);
                console.log(`Téléphone: ${user.telephone}`);
                console.log(`Statut: ${user.status}`);
                console.log(`Cohorte ID: ${user.cohorte_id}`);
                console.log(`Département ID: ${user.departement_id}`);
                console.log(`Photo: ${user.photo}`); // Affiche le chemin de la photo

                // Émettre les données utilisateur via WebSocket
                io.emit('rfid-card', {
                    type: 'card-data',
                    found: true,
                    userData: {
                        matricule: user.matricule,
                        nom: user.nom,
                        prenom: user.prenom,
                        statut: user.status,
                        cardID: user.cardID,
                        photo: user.photo, // Inclure la photo dans les données envoyées
                    }
                });

                // Gérer le pointage Check-In / Check-Out
                const currentDate = new Date();
                const dateStr = currentDate.toISOString().split('T')[0]; // Date du jour
                const hourStr = currentDate.toISOString().split('T')[1].substring(0, 5); // Heure du jour

                // Vérification s'il existe un Check-In pour l'utilisateur et la date actuelle
                const existingCheckIn = await ControleAcces.findOne({
                    userId: user.id,
                    date: dateStr,
                    type: 'Check-In',
                });

                if (!existingCheckIn) {
                    // Si aucun Check-In n'existe, créer un nouveau Check-In
                    const controleCheckIn = new ControleAcces({
                        userId: user.id,
                        date: dateStr,
                        heure: hourStr,
                        type: 'Check-In',
                        statut: 'En attente',
                        heureEntreePrevue: '09:00',
                        heureDescentePrevue: '17:00',
                        etat: 'Présent', // Définir comme "Présent" si c'est avant 17h
                    });
                    await controleCheckIn.save(); // Sauvegarde Check-In

                    console.log('Check-In enregistré pour:', user.matricule);

                    // Créer le Check-Out (par défaut, sans heure pour l'instant)
                    const controleCheckOut = new ControleAcces({
                        userId: user.id,
                        date: dateStr,
                        type: 'Check-Out',
                        statut: 'En attente',
                        heureEntreePrevue: '09:00',
                        heureDescentePrevue: '17:00',
                        etat: 'Absent',
                    });
                    await controleCheckOut.save(); // Sauvegarde Check-Out

                    console.log('Check-Out enregistré pour:', user.matricule);
                } else {
                    console.log('Check-In déjà enregistré pour cet utilisateur aujourd\'hui');
                    // Afficher le Check-In existant avec la date et l'heure
                    console.log('Check-In existant :');
                    console.log('Date Check-In:', existingCheckIn.date);
                    console.log('Heure Check-In:', existingCheckIn.heure);
                    console.log('Statut Check-In:', existingCheckIn.statut);
                    console.log('État Check-In:', existingCheckIn.etat);

                    // Si un Check-In existe déjà, créer ou mettre à jour le Check-Out
                    const existingCheckOut = await ControleAcces.findOne({
                        userId: user.id,
                        date: dateStr,
                        type: 'Check-Out',
                    });

                    if (existingCheckOut) {
                        // Mettre à jour le Check-Out avec l'heure actuelle à chaque scan
                        existingCheckOut.heure = hourStr;
                        existingCheckOut.statut = 'En attente'; // Mettre à jour le statut
                        existingCheckOut.etat = hourStr < '17:00' ? 'Présent' : 'Absent'; // Définir l'état
                        await existingCheckOut.save();

                        console.log('Check-Out mis à jour pour:', user.matricule);
                    } else {
                        console.log('Aucun Check-Out trouvé pour aujourd\'hui.');
                        // Si aucun Check-Out n'existe, créer un nouveau Check-Out
                        const controleCheckOut = new ControleAcces({
                            userId: user.id,
                            date: dateStr,
                            type: 'Check-Out',
                            statut: 'En attente',
                            heureEntreePrevue: '09:00',
                            heureDescentePrevue: '17:00',
                            etat: 'Absent', // Initialement "Absent"
                            heure: hourStr,
                        });
                        await controleCheckOut.save();

                        console.log('Nouveau Check-Out émis avec succès.');
                    }
                }
            } else {
                console.log('Utilisateur non trouvé');
                io.emit('rfid-card', {
                    type: 'card-data',
                    found: false,
                    message: 'Utilisateur non trouvé'
                });
            }
        } catch (error) {
            console.error('Erreur lors de la recherche utilisateur:', error);
            io.emit('rfid-card', {
                type: 'card-data',
                found: false,
                message: 'Erreur lors de la recherche'
            });
        }
    }
});

// WebSocket connection handling
io.on('connection', (socket) => {
    console.log('A user connected');

    socket.on('disconnect', () => {
        console.log('User disconnected');
    });
});

// Vérification périodique de la connexion au port série
setInterval(() => {
    if (!port.isOpen) {
        console.log('Port déconnecté, tentative de reconnexion...');
        port.open();
    }
}, 5000);

// Ajouter la route pour récupérer les pointages par cardID
app.get('/controle-acces/pointages/:cardID', async(req, res) => {
    const { cardID } = req.params;

    try {
        // Recherche l'utilisateur par cardID
        const user = await User.findOne({ cardID });

        if (!user) {
            return res.status(404).json({ message: 'Utilisateur non trouvé' });
        }

        // Recherche les pointages de l'utilisateur
        const pointages = await ControleAcces.find({ userId: user.id });

        if (pointages.length === 0) {
            return res.status(404).json({ message: 'Aucun pointage trouvé pour cet utilisateur' });
        }

        return res.json(pointages);
    } catch (error) {
        console.error('Erreur lors de la récupération des pointages:', error);
        return res.status(500).json({ message: 'Erreur serveur' });
    }
});

// Start server
server.listen(3000, () => {
    console.log('WebSocket server listening on port 3000');
});