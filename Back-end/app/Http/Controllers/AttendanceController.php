<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{


    public function getAttendanceList(Request $request)
    {
        try {
            $query = Attendance::with(['user' => function($query) {
                $query->select('_id', 'matricule', 'nom', 'prenom', 'departement');
            }]);
    
            // Filtre par date
            $date = $request->input('date', now()->format('Y-m-d'));
            $query->where('date', new \MongoDB\BSON\UTCDateTime(strtotime($date) * 1000));
    
            // Filtre par type d'employé (Employés/Apprenants)
            if ($request->has('type')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('type', $request->type);
                });
            }
    
            // Filtre de recherche
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('matricule', 'like', "%$search%")
                        ->orWhere('nom', 'like', "%$search%")
                        ->orWhere('prenom', 'like', "%$search%")
                        ->orWhere('departement', 'like', "%$search%");
                });
            }
    
            $attendances = $query->get()->map(function($attendance) {
                $entree = $attendance->first_check_in ? 
                    (new \DateTime($attendance->first_check_in))->format('H:i') : '--';
                $sortie = $attendance->last_check_out ? 
                    (new \DateTime($attendance->last_check_out))->format('H:i') : '--';
    
                // Déterminer le statut
                $status = $this->determineStatus($attendance->first_check_in);
    
                return [
                    'matricule' => $attendance->user->matricule,
                    'employe' => $attendance->user->nom . ' ' . $attendance->user->prenom,
                    'date' => (new \DateTime($attendance->date))->format('d M'),
                    'departement' => $attendance->user->departement,
                    'entree' => $entree,
                    'sortie' => $sortie,
                    'statut' => $status
                ];
            });
    
            // Calculer les statistiques pour le graphique
            $stats = [
                'total' => $attendances->count(),
                'presents' => $attendances->where('statut', 'A l\'heure')->count(),
                'retards' => $attendances->where('statut', 'Retard')->count(),
                'absents' => $attendances->where('statut', 'Absent')->count(),
                'conges' => $attendances->where('statut', 'Congés')->count(),
                'voyages' => $attendances->where('statut', 'Voyage')->count(),
            ];
    
            return response()->json([
                'attendances' => $attendances,
                'stats' => $stats,
                'total' => $stats['total']
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des présences',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Détermine le statut de présence en fonction de l'heure d'arrivée
     */
    private function determineStatus($checkInTime)
    {
        if (!$checkInTime) {
            return 'Absent';
        }
    
        $checkIn = new \DateTime($checkInTime);
        $startTime = new \DateTime('08:30');  // Heure limite d'arrivée
    
        return $checkIn <= $startTime ? 'A l\'heure' : 'Retard';
    }

    public function getAttendanceHistory(Request $request)
    {
        $query = Attendance::with('user:id,nom,prenom,matricule,photo,role');
        
        // Filtre par période
        switch($request->period) {
            case 'day':
                $query->whereDate('date', Carbon::today());
                break;
            case 'week':
                $query->whereBetween('date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('date', Carbon::now()->month);
                break;
        }
        
        // Filtre par type (retards/absences)
        if ($request->type === 'late') {
            $query->where('status', 'late');
        } elseif ($request->type === 'absent') {
            $query->whereNull('first_check_in');
        }
        
        // Appliquer les filtres communs
        $this->applyCommonFilters($query, $request);

        $attendances = $query->latest('date')->paginate(15);
        return response()->json($attendances);
    }

    public function validateAttendance(Request $request)
    {
        $attendance = Attendance::findOrFail($request->attendance_id);
        
        if ($request->is_validated) {
            $attendance->is_validated = true;
            $attendance->validated_by = auth()->user()->id;
            $attendance->save();
            
            return response()->json(['message' => 'Présence validée']);
        } else {
            $attendance->delete();
            return response()->json(['message' => 'Présence rejetée']);
        }
    }

    public function updateAttendance(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        
        // Seul l'admin peut modifier les présences
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $attendance->update([
            'status' => $request->status,
            'reason' => $request->reason, // congés, maladie, voyage
            'modified_by' => auth()->user()->id
        ]);

        return response()->json([
            'message' => 'Présence mise à jour',
            'attendance' => $attendance
        ]);
    }

    public function getDailyReport()
    {
        $today = Carbon::today();
        
        return response()->json([
            'date' => $today->format('Y-m-d'),
            'employes' => [
                'presents' => Attendance::whereDate('date', $today)
                    ->whereHas('user', fn($q) => $q->where('role', 'employe'))
                    ->where('status', 'present')
                    ->count(),
                'retards' => Attendance::whereDate('date', $today)
                    ->whereHas('user', fn($q) => $q->where('role', 'employe'))
                    ->where('status', 'late')
                    ->count()
            ],
            'apprenants' => [
                'presents' => Attendance::whereDate('date', $today)
                    ->whereHas('user', fn($q) => $q->where('role', 'apprenant'))
                    ->where('status', 'present')
                    ->count(),
                'retards' => Attendance::whereDate('date', $today)
                    ->whereHas('user', fn($q) => $q->where('role', 'apprenant'))
                    ->where('status', 'late')
                    ->count()
            ]
        ]);
    }

    /**
     * Applique les filtres communs aux requêtes d'assiduité
     */
    private function applyCommonFilters($query, Request $request)
    {
        if ($request->role) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('role', $request->role);
            });
        }

        if ($request->departement_id) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('departement_id', $request->departement_id);
            });
        }

        if ($request->cohorte_id) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('cohorte_id', $request->cohorte_id);
            });
        }
    }
}