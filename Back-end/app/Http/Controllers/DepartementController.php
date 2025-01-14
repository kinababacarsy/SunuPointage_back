<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DepartementController extends Controller
{
    /**
     * Liste des départements avec le nombre d'employés
     */
    public function list()
    {
        // Récupérer tous les départements avec leurs utilisateurs
        $departements = Departement::with('users')->get();

        // Ajouter le nombre d'employés à chaque département
        $departements->each(function ($departement) {
            $departement->nbre_employe = $departement->users->count();
          
        });

        return response()->json($departements);
    }

    /**
     * Créer un département
     */
    /**
     * Créer un département
     */
    public function create(Request $request)
    {
        try {
            $request->validate([
                'nom_departement' => 'required|string|max:255|unique:departements,nom_departement',
                'description' => 'nullable|string',
            ]);

            // Créer le département
            $departement = Departement::create($request->all());

            return response()->json($departement, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => [
                    'nom_departement' => ['Le nom du département est déjà pris.'],
                ],
            ], 422);
        }
    }

    /**
     * Afficher un département avec le nombre d'employés
     */
    public function view($id)
    {
        // Récupérer le département avec ses utilisateurs
        $departement = Departement::with('users')->findOrFail($id);

        // Ajouter le nombre d'employés
        $departement->nbre_employe = $departement->users->count();

        return response()->json($departement);
    }

    /**
     * Mettre à jour un département
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'nom_departement' => 'sometimes|required|string|max:255|unique:departements,nom_departement,' . $id,
                'description' => 'nullable|string',
            ]);

            // Mettre à jour le département
            $departement = Departement::findOrFail($id);
            $departement->update($request->all());

            return response()->json($departement, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => [
                    'nom_departement' => ['Le nom du département est déjà pris.'],
                ],
            ], 422);
        }
    }

    /**
     * Supprimer un département
     */
    public function delete($id)
    {
        // Supprimer le département
        $departement = Departement::findOrFail($id);
        $departement->delete();

        return response()->json(null, 204);
    }

    /**
     * Récupérer le nombre total de départements
     */
    public function count()
    {
        // Récupérer le nombre total de départements
        $totalDepartements = Departement::count();

        return response()->json(['total_departements' => $totalDepartements]);
    }
}