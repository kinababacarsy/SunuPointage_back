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
            'userId' => 'required|exists:users,_id', // Vérifier si l'utilisateur existe dans la collection 'users'
            'statut' => 'nullable|in:Approuve,Rejete,En attente', // Le statut est optionnel et sera défini par défaut sur "En attente" si non fourni
        ]);
    
        try {
            // Vérifier si l'utilisateur existe dans la base de données
            $user = User::find($validatedData['userId']);
            if (!$user) {
                return response()->json([
                    'message' => 'Utilisateur non trouvé.'
                ], 404);
            }
    
            // Récupérer les informations de l'utilisateur (matricule, nom, prénom, statut)
            $userInfo = [
                'matricule' => $user->matricule ?? 'Non défini',
                'nom' => $user->nom ?? 'Non défini',
                'prenom' => $user->prenom ?? 'Non défini',
                'statut' => $user->status ?? 'Actif',
            ];
    
            // Si le statut n'est pas fourni, définir "En attente" par défaut
            $statut = $validatedData['statut'] ?? 'En attente';
    
            // Calcul de l'heure actuelle
            $heureEnregistree = Carbon::now(); // Utiliser l'heure actuelle pour 'heure'
    
            // Heure d'entrée et de descente prévues
            $heureEntreePrevue = Carbon::createFromFormat('H:i', '09:00');
            $heureDescentePrevue = Carbon::createFromFormat('H:i', '17:00');
    
            // Logique pour définir l'état
            if ($heureEnregistree->greaterThan($heureDescentePrevue)) {
                $etat = 'Absent'; // Si l'heure actuelle est après l'heure de descente
            } elseif ($heureEnregistree->greaterThan($heureEntreePrevue)) {
                $etat = 'Retard'; // Si l'heure actuelle est après l'heure d'entrée
            } else {
                $etat = 'Present'; // Sinon, l'état est 'Présent'
            }
    
            // Vérifier si l'utilisateur a déjà un Check-In pour aujourd'hui
            $checkInExist = ControleAcces::where('userId', $validatedData['userId'])
                                        ->where('date', $heureEnregistree->format('Y-m-d'))
                                        ->where('type', 'Check-In')
                                        ->first();
    
            // Si un Check-In existe, on enregistre normalement le Check-Out
            if ($checkInExist) {
                // Vérifier s'il existe déjà un Check-Out pour cet utilisateur et cette journée
                $checkOutExist = ControleAcces::where('userId', $validatedData['userId'])
                                            ->where('date', $heureEnregistree->format('Y-m-d'))
                                            ->where('type', 'Check-Out')
                                            ->first();
    
                if ($checkOutExist) {
                    // Mise à jour du Check-Out existant
                    $checkOutExist->heure = $heureEnregistree->format('H:i');
                    $checkOutExist->statut = $statut;
                    $checkOutExist->etat = $etat;
                    $checkOutExist->save();
                    return response()->json([
                        'message' => 'Check-Out mis à jour avec succès !',
                        'controleAcces' => $checkOutExist,
                        'userInfo' => $userInfo
                    ], 200);
                } else {
                    // Enregistrement d'un nouveau Check-Out
                    $controleAcces = new ControleAcces();
                    $controleAcces->userId = $validatedData['userId'];
                    $controleAcces->date = $heureEnregistree->format('Y-m-d');
                    $controleAcces->heure = $heureEnregistree->format('H:i');
                    $controleAcces->type = 'Check-Out';
                    $controleAcces->statut = $statut;
                    $controleAcces->heureEntreePrevue = '09:00';
                    $controleAcces->heureDescentePrevue = '17:00';
                    $controleAcces->etat = $etat;
                    $controleAcces->save();
    
                    return response()->json([
                        'message' => 'Check-Out enregistré avec succès !',
                        'controleAcces' => $controleAcces,
                        'userInfo' => $userInfo
                    ], 201);
                }
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
