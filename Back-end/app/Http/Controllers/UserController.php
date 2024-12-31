<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Departement;
use App\Models\Cohorte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function list()
    {
        $users = Users::all();
        return response()->json($users, 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telephone' => ['required', 'regex:/^[0-9]{9}$/'], // Validation pour 9 chiffres            
            'adresse' => 'nullable|string',
            'photo' => 'nullable|string', // Permet de soumettre une photo, sinon on prendra la valeur par défaut            
            'role' => 'required|in:employe,apprenant',
            'departement_id' => 'nullable|string',
            'cohorte_id' => 'nullable|string',
            'cardID' => 'nullable|string',
            'rfid_status' => 'Boolean (État actif/inactif de la carte RFID)',
            'status' => 'required|boolean (État actif/inactif de utilisateur).', 

        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Générer le matricule
        $matricule = $this->generateMatricule($request->role);

        // Préparer les données
        $data = $request->all();
        $data['matricule'] = $matricule;

        // Si le rôle est employe, affecter le département et nullifier la cohorte
        if ($request->role === 'employe') {
            $data['cohorte_id'] = null;
            if ($request->departement_id) {
                $departement = Departement::findOrFail($request->departement_id);
                $departement->nbre_employe += 1;
                $departement->save();
            }
        }

        // Si le rôle est apprenant, affecter la cohorte et nullifier le département
        if ($request->role === 'apprenant') {
            $data['departement_id'] = null;
            if ($request->cohorte_id) {
                $cohorte = Cohorte::findOrFail($request->cohorte_id);
                $cohorte->nbre_apprenant += 1;
                $cohorte->save();
            }
        }

          // Vérification si une photo a été envoyée, sinon définir la photo par défaut
          if (empty($validatedData['photo'])) {
            $validatedData['photo'] = 'images/inconnu.png'; // Spécifier le chemin de l'image par défaut
        }

        
        // Créer l'utilisateur
        $user = Users::create($data);

        return response()->json($user, 201);
    }

    public function createFromDepartement(Request $request, $departement_id)
    {
        $request->merge(['departement_id' => $departement_id, 'role' => 'employe']);
        return $this->create($request);
    }

    public function createFromCohorte(Request $request, $cohorte_id)
    {
        $request->merge(['cohorte_id' => $cohorte_id, 'role' => 'apprenant']);
        return $this->create($request);
    }


    public function view($id)
    {
        $user = Users::findOrFail($id);
        return response()->json($user);
    }



    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:255',
            'prenom' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'telephone' => 'sometimes|required|string|max:20',
            'adresse' => 'nullable|string',
            'photo' => 'nullable|string',
            'role' => 'sometimes|required|in:employe,apprenant',
            'departement_id' => 'nullable|string',
            'cohorte_id' => 'nullable|string',
            'cardID' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Users::findOrFail($id);
        $user->update($request->all());

        return response()->json($user, 200);
    }

    public function delete($id)
    {
        $user = Users::findOrFail($id);
        $user->delete();
        return response()->json(null, 204);
    }

     // Suppression multiple
     public function deleteMultiple(Request $request)
     {
         $validator = Validator::make($request->all(), [
             'ids' => 'required|array|min:1',
             'ids.*' => 'exists:users,_id',
         ]);
     
         if ($validator->fails()) {
             return response()->json($validator->errors(), 400);
         }
     
         // Vérification : Empêcher la suppression d'administrateurs principaux
         $criticalUsers = Users::whereIn('_id', $request->ids)->where('role', 'admin')->exists();
         if ($criticalUsers) {
             return response()->json(['message' => 'Impossible de supprimer un administrateur principal.'], 400);
         }
     
         Users::whereIn('_id', $request->ids)->delete();
         return response()->json(['message' => 'Utilisateurs supprimés avec succès'], 200);
     }
     
 
     // Blocage/Déblocage d'un utilisateur
     public function toggleStatus($id)
     {
         $user = Users::findOrFail($id);
         $user->status = !$user->status; // Inverse le statut (actif/inactif)
         $user->save();
 
         $message = $user->status ? 'Utilisateur activé avec succès' : 'Utilisateur désactivé avec succès';
         return response()->json(['message' => $message, 'user' => $user], 200);
     }
 

    private function generateMatricule($role)
    {
        $prefix = ($role === 'employe') ? 'EMP' : 'APP';
        $lastUser = Users::where('role', $role)->orderBy('created_at', 'desc')->first();
        $lastNumber = $lastUser ? (int)substr($lastUser->matricule, 3) : 0;
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        return $prefix . $newNumber;
    }

    public function bloquerUtilisateur($id)
    {
        $user = Users::find($id);
    
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
        }
    
        $user->status = false;
        $user->save();
    
        // Désactiver la carte RFID associée
        $user->rfid_status = false; // Ajoute ce champ si nécessaire
        $user->save();
    
        return response()->json([
            'message' => 'Utilisateur bloqué, carte RFID désactivée.',
            'user' => $user,
        ], 200);
    }
    
    public function debloquerUtilisateur($id)
{
    $user = Users::find($id);

    if (!$user) {
        return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
    }

    $user->status = true;
    $user->save();

    // Activer la carte RFID associée
    $user->rfid_status = true; // Ajoute ce champ si nécessaire
    $user->save();

    return response()->json([
        'message' => 'Utilisateur débloqué, carte RFID activée.',
        'user' => $user,
    ], 200);
}

public function lireCarte(Request $request)
{
    $cardID = $request->input('cardID');
    $user = Users::where('cardID', $cardID)->first();

    if (!$user) {
        return response()->json(['message' => 'Carte non reconnue.'], 404);
    }

    if (!$user->rfid_status) {
        return response()->json(['message' => 'Carte inactive.'], 403);
    }

    return response()->json([
        'message' => 'Carte valide.',
        'user' => $user,
    ], 200);
}


public function assignerCarteRFID(Request $request, $id)
{
    $user = Users::find($id);

    if (!$user) {
        return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
    }

    $cardID = $request->input('cardID');

    // Vérifier si la carte est déjà assignée
    $existingUser = Users::where('cardID', $cardID)->first();
    if ($existingUser) {
        return response()->json(['message' => 'Cette carte RFID est déjà assignée à un autre utilisateur.'], 400);
    }
      // Vérifier si la carte est inactive
      if ($existingUser && !$existingUser->rfid_status) {
        return response()->json(['message' => 'Cette carte est inactive.'], 400);
    }

    // Assigner la carte RFID
    $user->cardID = $cardID;
    $user->rfid_status = true; // Active par défaut lors de l'assignation
    $user->save();

    return response()->json([
        'message' => 'Carte RFID assignée avec succès.',
        'user' => $user,
    ], 200);
}

public function activerCarte($cardID)
{
    $user = Users::where('cardID', $cardID)->first();

    if (!$user) {
        return response()->json(['message' => 'Carte non trouvée.'], 404);
    }

    $user->rfid_status = true;
    $user->save();

    return response()->json(['message' => 'Carte activée avec succès.', 'user' => $user], 200);
}

public function desactiverCarte($cardID)
{
    $user = Users::where('cardID', $cardID)->first();

    if (!$user) {
        return response()->json(['message' => 'Carte non trouvée.'], 404);
    }

    $user->rfid_status = false;
    $user->save();

    return response()->json(['message' => 'Carte désactivée avec succès.', 'user' => $user], 200);
}


}
