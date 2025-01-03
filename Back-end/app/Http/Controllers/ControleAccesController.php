<?php 

namespace App\Http\Controllers;

use App\Models\ControleAcces;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ControleAccesController extends Controller
{
    // Enregistrer un pointage Check-In ou Check-Out
    public function store(Request $request)
    {
        // Validation des données d'entrée
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
                // Si aucun Check-In n'existe, on enregistre un Check-In
                $controleAcces = new ControleAcces();
                $controleAcces->userId = $validatedData['userId'];
                $controleAcces->date = $heureEnregistree->format('Y-m-d');
                $controleAcces->heure = $heureEnregistree->format('H:i');
                $controleAcces->type = 'Check-In';
                $controleAcces->statut = $statut;
                $controleAcces->heureEntreePrevue = '09:00';
                $controleAcces->heureDescentePrevue = '17:00';
                $controleAcces->etat = $etat;
                $controleAcces->save();
    
                return response()->json([
                    'message' => 'Check-In enregistré avec succès !',
                    'controleAcces' => $controleAcces,
                    'userInfo' => $userInfo
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'enregistrement du pointage.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
// Récupérer tous les pointages d'un utilisateur
public function show($userId)
{
    try {
        // Vérifier si l'utilisateur existe dans la base de données
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé.'
            ], 404);
        }

        // Récupérer tous les pointages pour cet utilisateur
        $controleAcces = ControleAcces::where('userId', $userId)->get();

        // Si aucun pointage trouvé, retourner un message spécifique
        if ($controleAcces->isEmpty()) {
            return response()->json([
                'message' => 'Aucun pointage trouvé pour cet utilisateur.'
            ], 404);
        }

        // Retourner les pointages sous forme de réponse JSON
        return response()->json([
            'controleAcces' => $controleAcces
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Erreur lors de la récupération des pointages.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    // Récupérer tous les pointages de tous les utilisateurs
    public function getAll()
    {
        // Récupérer tous les pointages
        $controleAcces = ControleAcces::all();

        if ($controleAcces->isEmpty()) {
            return response()->json([
                'message' => 'Aucun pointage trouvé.'
            ], 404);
        }

        return response()->json($controleAcces, 200);
    }

    // Supprimer un pointage spécifique
    public function destroy($id)
    {
        // Trouver le pointage à supprimer
        $controleAcces = ControleAcces::find($id);

        if (!$controleAcces) {
            return response()->json([
                'message' => 'Pointage non trouvé.'
            ], 404);
        }

        // Supprimer le pointage
        $controleAcces->delete();

        return response()->json([
            'message' => 'Pointage supprimé avec succès.'
        ], 200);
    }
}
