<?php

namespace App\Http\Controllers;

use App\Models\ControleAcces;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ControleAccesController extends Controller
{
    public function store(Request $request)
    {
        // Validation des données reçues
        $validatedData = $request->validate([
            'userId' => 'required|string', // Le userId est obligatoire pour identifier l'utilisateur
        ]);
    
        // Trouver l'utilisateur basé sur le userId
        $user = User::where('_id', $validatedData['userId'])->first();
    
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }
    
        // Récupérer la date d'aujourd'hui
        $dateDuJour = Carbon::now()->format('Y-m-d');
        $heureActuelle = Carbon::now()->format('H:i:s');
    
        // Rechercher un Check-In existant pour cet utilisateur et cette date
        $checkInExistant = ControleAcces::where('userId', $user->_id)
            ->whereDate('date', $dateDuJour)
            ->where('type', 'Check-In') // Vérifier si un Check-In existe déjà
            ->first();
    
        // Si un Check-In existe déjà
        if ($checkInExistant) {
            // Vérifier s'il existe un Check-Out
            $checkOutExistant = ControleAcces::where('userId', $user->_id)
                ->whereDate('date', $dateDuJour)
                ->where('type', 'Check-Out')
                ->first();
    
            // Si un Check-Out n'existe pas, créer un nouveau Check-Out
            if (!$checkOutExistant) {
                // Créer un nouveau Check-Out
                $checkOut = new ControleAcces([
                    'userId' => $user->_id,
                    'date' => $dateDuJour,
                    'heure' => $heureActuelle, // L'heure de Check-Out
                    'type' => 'Check-Out', // Type de pointage
                    'statut' => 'En attente', // Statut par défaut
                    'heureEntreePrevue' => '09:00:00', // Heure d'entrée prévue
                    'heureDescentePrevue' => '17:00:00', // Heure de descente prévue
                    'etat' => 'Present', // L'état est 'Present' au moment du Check-Out
                ]);
                $checkOut->save();
    
                return response()->json([
                    'message' => 'Check-Out enregistré avec succès.',
                    'controleAcces' => $checkOut,
                ], 201);
            } else {
                // Si un Check-Out existe déjà, mettre à jour l'heure de Check-Out
                $checkOutExistant->heure = $heureActuelle;
                $checkOutExistant->save();
    
                return response()->json([
                    'message' => 'Check-Out mis à jour avec succès.',
                    'controleAcces' => $checkOutExistant,
                ], 200);
            }
        }
    
        // Si aucun Check-In n'existe, cela signifie que c'est le premier pointage de la journée, donc un Check-In
        else {
            // Calculer le retard si l'heure de Check-In est après 9h00
            $heureEntreePrevue = '09:00:00';
            $retard = null;
    
            if ($heureActuelle > $heureEntreePrevue) {
                $retard = Carbon::parse($heureActuelle)->diffInMinutes($heureEntreePrevue);
            }
    
            // Créer un nouveau Check-In
            $checkIn = new ControleAcces([
                'userId' => $user->_id,
                'date' => $dateDuJour,
                'heure' => $heureActuelle, // L'heure de Check-In
                'type' => 'Check-In',
                'statut' => 'En attente',
                'heureEntreePrevue' => $heureEntreePrevue, // Heure d'entrée prévue
                'heureDescentePrevue' => '17:00:00', // Heure de descente prévue
                'etat' => 'Present', // L'état est 'Present' au moment du Check-In
                'retard' => $retard, // Retard en minutes (si applicable)
            ]);
            $checkIn->save();
    
            return response()->json([
                'message' => 'Check-In enregistré avec succès.',
                'controleAcces' => $checkIn,
            ], 201);
        }
    }

    // Lister tous les pointages
    public function index()
    {
        $controleAcces = ControleAcces::all(); // Récupérer tous les pointages
        return response()->json($controleAcces, 200);
    }

    // Récupérer un pointage spécifique par ID
    public function show($id)
    {
        $controleAcces = ControleAcces::find($id); // Trouver un pointage par son ID

        if (!$controleAcces) {
            return response()->json(['message' => 'Pointage non trouvé'], 404);
        }

        return response()->json($controleAcces, 200);
    }

    public function getPointagesByUserId($userId)
    {
        // Trouver l'utilisateur basé sur le userId
        $user = User::where('_id', $userId)->first();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Récupérer la date d'aujourd'hui
        $dateDuJour = Carbon::now()->format('Y-m-d');

        // Récupérer tous les pointages pour cet utilisateur basé sur son userId et la date d'aujourd'hui
        $pointages = ControleAcces::where('userId', $user->_id)
            ->whereDate('date', $dateDuJour)
            ->get();

        if ($pointages->isEmpty()) {
            return response()->json(['message' => 'Aucun pointage trouvé pour cet utilisateur aujourd\'hui.'], 404);
        }

        return response()->json($pointages, 200);
    }
    public function storeAbsence(Request $request)
    {
        // Validation des données reçues
        $validatedData = $request->validate([
            'userId' => 'required|string',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'absenceType' => 'required|string',
            'description' => 'nullable|string',
        ]);
    
        // Trouver l'utilisateur basé sur le userId
        $user = User::where('_id', $validatedData['userId'])->first();
    
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }
    
        // Convertir les dates en objets Carbon
        $startDate = Carbon::parse($validatedData['startDate']);
        $endDate = Carbon::parse($validatedData['endDate']);
    
        // Enregistrer chaque jour d'absence
        while ($startDate <= $endDate) {
            $absence = new ControleAcces([
                'userId' => $user->_id,
                'date' => $startDate->format('Y-m-d'),
                'type' => 'Absence',
                'statut' => 'En attente',
                'absenceType' => $validatedData['absenceType'],
                'description' => $validatedData['description'],
                'etat' => 'Absent', // L'état est 'Absent' pour une absence
            ]);
            $absence->save();
    
            $startDate->addDay(); // Passer au jour suivant
        }
    
        return response()->json([
            'message' => 'Absence enregistrée avec succès.',
        ], 201);
    }
    public function getAbsencesByUserId($userId, Request $request)
    {
        // Validation des paramètres de la requête
        $request->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date',
        ]);
    
        // Trouver l'utilisateur basé sur le userId
        $user = User::where('_id', $userId)->first();
    
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }
    
        // Récupérer les absences pour la plage de dates spécifiée
        $absences = ControleAcces::where('userId', $user->_id)
            ->where('type', 'Absence') // Filtrer uniquement les absences
            ->whereBetween('date', [$request->startDate, $request->endDate])
            ->get();
    
        return response()->json($absences, 200);
    }
}
