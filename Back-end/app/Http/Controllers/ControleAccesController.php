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
                    'heure' => Carbon::now()->format('H:i:s'), // L'heure de Check-Out
                    'type' => 'Check-Out', // Type de pointage
                    'statut' => 'En attente', // Statut par défaut
                    'heureEntreePrevue' => '09:00:00', // Heure d'entrée prévue
                    'heureDescentePrevue' => '17:00:00', // Heure de descente prévue
                    'etat' => 'Absent', // Par défaut, l'état est Absent
                ]);
                $checkOut->save();

                return response()->json([
                    'message' => 'Check-Out enregistré avec succès.',
                    'controleAcces' => $checkOut,
                ], 201);
            } else {
                // Si un Check-Out existe déjà, mettre à jour l'heure de Check-Out
                $checkOutExistant->heure = Carbon::now()->format('H:i:s');
                $checkOutExistant->save();

                return response()->json([
                    'message' => 'Check-Out mis à jour avec succès.',
                    'controleAcces' => $checkOutExistant,
                ], 200);
            }
        }

        // Si aucun Check-In n'existe, cela signifie que c'est le premier pointage de la journée, donc un Check-In
        else {
            // Créer un nouveau Check-In
            $checkIn = new ControleAcces([
                'userId' => $user->_id,
                'date' => $dateDuJour,
                'heure' => Carbon::now()->format('H:i:s'), // L'heure de Check-In
                'type' => 'Check-In',
                'statut' => 'En attente',
                'heureEntreePrevue' => '09:00:00', // Heure d'entrée prévue
                'heureDescentePrevue' => '17:00:00', // Heure de descente prévue
                'etat' => 'Present', // L'état est 'Present' au moment du Check-In
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
}
