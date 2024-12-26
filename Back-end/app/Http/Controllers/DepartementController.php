<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use Illuminate\Http\Request;

class DepartementController extends Controller
{
    public function list()
    {
        $departements = Departement::all();
        return response()->json($departements);
    }

    public function create(Request $request)
    {
        $request->validate([
            'nom_departement' => 'required|string|max:255',
            'nbre_employe' => 'required|integer',
            'description' => 'nullable|string',
            'date_creation' => 'required|date',
            'date_modification' => 'nullable|date',
        ]);

        $departement = Departement::create($request->all());
        return response()->json($departement, 201);
    }

    public function view($id)
    {
        $departement = Departement::findOrFail($id);
        return response()->json($departement);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom_departement' => 'sometimes|required|string|max:255',
            'nbre_employe' => 'sometimes|required|integer',
            'description' => 'nullable|string',
            'date_creation' => 'sometimes|required|date',
            'date_modification' => 'nullable|date',
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
