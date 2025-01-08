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
    .catch(err => console.error('MongoDB connection error:', err));

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

                // Émettre les données utilisateur via WebSocket
                io.emit('rfid-card', {
                    type: 'card-data',
                    found: true,
                    userData: {
                        matricule: user.matricule,
                        nom: user.nom,
                        prenom: user.prenom,
                        statut: user.status,
                        cardID: user.cardID
                    }
                });
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

    // Fetch user info from the database
    socket.on('request-user-info', async(cardID) => {
        try {
            const user = await User.findOne({ cardID: cardID });
            if (user) {
                socket.emit('user-data', {
                    type: 'user-info',
                    found: true,
                    userData: {
                        matricule: user.matricule,
                        nom: user.nom,
                        prenom: user.prenom,
                        statut: user.status,
                        cardID: user.cardID
                    }
                });
            } else {
                socket.emit('user-data', {
                    type: 'user-info',
                    found: false,
                    message: 'Utilisateur non trouvé'
                });
            }
        } catch (error) {
            console.error('Error fetching user:', error);
            socket.emit('user-data', {
                type: 'user-info',
                found: false,
                message: 'Erreur lors de la recherche'
            });
        }
    });

    // Handle checkin event
    socket.on('checkin', async(data) => {
        const { cardID } = data;
        try {
            const user = await User.findOne({ cardID: cardID });
            if (user) {
                // Ne pas mettre à jour premierPointage car il n'existe pas dans le modèle
                const controle = new ControleAcces({
                    userId: user.id,
                    status: 'success',
                    time: new Date()
                });
                await controle.save();

                io.emit('checkin-status', {
                    type: 'checkin-status',
                    status: 'success',
                    userData: {
                        matricule: user.matricule,
                        nom: user.nom,
                        prenom: user.prenom
                    }
                });
            } else {
                io.emit('checkin-status', {
                    type: 'checkin-status',
                    status: 'failure',
                    message: 'Utilisateur non trouvé'
                });
            }
        } catch (error) {
            console.error('Check-in error:', error);
            io.emit('checkin-status', {
                type: 'checkin-status',
                status: 'error',
                message: 'Erreur lors du check-in'
            });
        }
    });

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

// Start server
server.listen(3000, () => {
    console.log('WebSocket server listening on port 3000');
});