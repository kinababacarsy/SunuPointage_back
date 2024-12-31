<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // 1. Lister tous les utilisateurs
    public function index()
    {
        $users = Users::all();
        return response()->json($users);
    }

    // 2. Créer un nouvel utilisateur
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'telephone' => 'required|string',
            'adresse' => 'required|string',
            'role' => 'required|in:admin,vigile,apprenant',
            'statut' => 'required|boolean',
            'photo' => 'nullable|image|max:2048',
            'departement_id' => 'nullable|string',
            'cohorte_id' => 'nullable|string',
            'cardID' => 'required|string|unique:users'
        ]);

        $matricule = 'USER-' . strtoupper(Str::random(6)); // Génération du matricule
        $userData = $request->all();
        $userData['matricule'] = $matricule;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photos', 'public');
            $userData['photo'] = $photoPath;
        }

        $user = Users::create($userData);

        return response()->json($user, 201);
    }

    // 3. Obtenir les détails d'un utilisateur
    public function show($id)
    {
        $user = Users::find($id);
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        return response()->json($user);
    }

    // 4. Mettre à jour un utilisateur
    public function update(Request $request, $id)
    {
        $user = Users::find($id);
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $request->validate([
            'email' => 'email|unique:users,email,' . $id,
            'role' => 'in:admin,vigile,apprenant',
            'photo' => 'nullable|image|max:2048',
            'cardID' => 'string|unique:users,cardID,' . $id,
        ]);

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photos', 'public');
            $user->photo = $photoPath;
        }

        $user->update($request->all());

        return response()->json($user);
    }

    // 5. Supprimer un utilisateur
    public function destroy($id)
    {
        $user = Users::find($id);
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé avec succès']);
    }
}
