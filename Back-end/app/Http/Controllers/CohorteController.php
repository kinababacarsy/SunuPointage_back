<?php

namespace App\Http\Controllers;

use App\Models\Cohorte;
use Illuminate\Http\Request;

class CohorteController extends Controller
{
    public function list()
    {
        $cohortes = Cohorte::all();
        return response()->json($cohortes);
    }

    public function create(Request $request)
    {
        $request->validate([
            'nom_cohorte' => 'required|string|max:255',
            'nbre_employe' => 'required|integer',
            'description' => 'nullable|string',
            'date_creation' => 'required|date',
            'date_modification' => 'nullable|date',
        ]);

        $cohorte = Cohorte::create($request->all());
        return response()->json($cohorte, 201);
    }

    public function view($id)
    {
        $cohorte = Cohorte::findOrFail($id);
        return response()->json($cohorte);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom_cohorte' => 'sometimes|required|string|max:255',
            'nbre_employe' => 'sometimes|required|integer',
            'description' => 'nullable|string',
            'date_creation' => 'sometimes|required|date',
            'date_modification' => 'nullable|date',
        ]);

        $cohorte = Cohorte::findOrFail($id);
        $cohorte->update($request->all());
        return response()->json($cohorte, 200);
    }

    public function delete($id)
    {
        $cohorte = Cohorte::findOrFail($id);
        $cohorte->delete();
        return response()->json(null, 204);
    }
}
