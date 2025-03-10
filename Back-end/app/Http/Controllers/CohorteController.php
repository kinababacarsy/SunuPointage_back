<?php
// CohorteController.php
namespace App\Http\Controllers;

use App\Models\Cohorte;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CohorteController extends Controller

{

    /**
     * Liste des cohortes avec le nombre d'apprenants
     */
    public function list()
    {
        // Récupérer toutes les cohortes avec leurs utilisateurs
        $cohortes = Cohorte::with('users')->get();

        // Ajouter le nombre d'apprenants à chaque cohorte
        $cohortes->each(function ($cohorte) {
            $cohorte->nbre_apprenant = $cohorte->users->where('role', 'apprenant')->count();
        });

        return response()->json($cohortes);
    }

    /**
     * Créer une cohorte
     */
    public function create(Request $request)
    {
        try {
            $request->validate([
                'nom_cohorte' => 'required|string|max:255|unique:cohortes,nom_cohorte',
                'description' => 'nullable|string',
            ]);

            // Créer la cohorte
            $cohorte = Cohorte::create($request->all());

            return response()->json($cohorte, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => [
                    'nom_cohorte' => ['Le nom de la cohorte est déjà pris.'],
                ],
            ], 422);
        }
    }

    /**
     * Afficher une cohorte avec le nombre d'apprenants
     */
    public function view($id)
    {
        // Récupérer la cohorte avec ses utilisateurs
        $cohorte = Cohorte::with('users')->findOrFail($id);

        // Ajouter le nombre d'apprenants
        $cohorte->nbre_apprenant = $cohorte->users->where('role', 'apprenant')->count();

        return response()->json($cohorte);
    }

    /**
     * Mettre à jour une cohorte
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'nom_cohorte' => 'sometimes|required|string|max:255|unique:cohortes,nom_cohorte,' . $id,
                'description' => 'nullable|string',
            ]);

            // Mettre à jour la cohorte
            $cohorte = Cohorte::findOrFail($id);
            $cohorte->update($request->all());

            return response()->json($cohorte, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => [
                    'nom_cohorte' => ['Le nom de la cohorte est déjà pris.'],
                ],
            ], 422);
        }
    }

    /**
     * Supprimer une cohorte
     */
    public function delete($id)
    {
        // Supprimer la cohorte
        $cohorte = Cohorte::findOrFail($id);
        $cohorte->delete();

        return response()->json(null, 204);
    }

    /**
     * Récupérer le nombre total de cohortes
     */
    public function count()
    {
        // Récupérer le nombre total de cohortes
        $totalCohortes = Cohorte::count();

        return response()->json(['total_cohortes' => $totalCohortes]);
    }
}
