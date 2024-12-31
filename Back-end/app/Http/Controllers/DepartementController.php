<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use Illuminate\Http\Request;

class DepartementController extends Controller
{
    public function list()
    {
        $departements = Departement::with('users')->get();
        return response()->json($departements);
    }

    public function create(Request $request)
    {
        $request->validate([
            'nom_departement' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Définir nbre_employe à zéro si non fourni
        $data = $request->all();
        $data['nbre_employe'] = $data['nbre_employe'] ?? 0;

        $departement = Departement::create($data);
        return response()->json($departement, 201);
    }

    public function view($id)
    {
        $departement = Departement::with('users')->findOrFail($id);
        return response()->json($departement);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom_departement' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $departement = Departement::findOrFail($id);
        $departement->update($request->all());
        return response()->json($departement, 200);
    }

    public function delete($id)
    {
        $departement = Departement::findOrFail($id);
        $departement->delete();
        return response()->json(null, 204);
    }
}
