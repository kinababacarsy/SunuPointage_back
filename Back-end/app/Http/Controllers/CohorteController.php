<?php

namespace App\Http\Controllers;

use App\Models\Cohorte;
use Illuminate\Http\Request;

class CohorteController extends Controller

{

    public function list()
    {
        $cohortes = Cohorte::with('users')->get();
        return response()->json($cohortes);
    }

    public function create(Request $request)
    {
        $request->validate([
            'nom_cohorte' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Définir nbre_apprenant à zéro si non fourni
        $data = $request->all();
        $data['nbre_apprenant'] = $data['nbre_apprenant'] ?? 0;

        $cohorte = Cohorte::create($data);
        return response()->json($cohorte, 201);
    }

    public function view($id)
    {
        $cohorte = Cohorte::with('users')->findOrFail($id);
        return response()->json($cohorte);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom_cohorte' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
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
