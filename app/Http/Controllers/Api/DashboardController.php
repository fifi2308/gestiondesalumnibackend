<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Offre;
use App\Models\Evenement;
use App\Models\Entreprise;
use App\Models\Etudiant;
use App\Models\Groupe;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats()
    {
        // 🔹 Compteurs
        $totalAlumni = User::where('role', 'alumni')->count();
        $totalEtudiant = User::where('role', 'etudiant')->count();
        $totalEntreprise = User::where('role', 'entreprise')->count();
        $totalOffres = Offre::count();
        $totalEvenements = Evenement::count();
        $totalGroupes = Groupe::count();



        $currentYear = date('Y');

        // 🔹 Évolution mensuelle des utilisateurs
        $usersPerMonth = User::select(
            DB::raw("EXTRACT(MONTH FROM created_at) as month"),
            DB::raw("COUNT(*) as count")
        )
        ->whereRaw("EXTRACT(YEAR FROM created_at) = ?", [$currentYear])
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        // 🔹 Évolution mensuelle des offres
        $offresPerMonth = Offre::select(
            DB::raw("EXTRACT(MONTH FROM created_at) as month"),
            DB::raw("COUNT(*) as count")
        )
        ->whereRaw("EXTRACT(YEAR FROM created_at) = ?", [$currentYear])
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        // 🔹 Évolution mensuelle des événements
        $evenementsPerMonth = Evenement::select(
            DB::raw("EXTRACT(MONTH FROM created_at) as month"),
            DB::raw("COUNT(*) as count")
        )
        ->whereRaw("EXTRACT(YEAR FROM created_at) = ?", [$currentYear])
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        return response()->json([
            'totalAlumni' => $totalAlumni,
            'totalOffres' => $totalOffres,
            'totalEvenements' => $totalEvenements,


            'totalEntreprises' =>  $totalEntreprise,
            'totalEtudiants' =>  $totalEtudiant,
            'totalGroupes' => $totalGroupes,


            'usersPerMonth' => $usersPerMonth,
            'offresPerMonth' => $offresPerMonth,
            'evenementsPerMonth' => $evenementsPerMonth,
        ]);
    }
}
