<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\FixedLeaves;
use App\Models\LeavesBalance;
use App\Models\PublicHoliday;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class HomeEmployeeController extends Controller
{
    public function getAuthenticatedUserInfo()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'error' => 'Utilisateur non authentifié'
                ], 401);
            }

            return response()->json([
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'avatar_path' => $user->avatar_path,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur inattendue',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function upcomingPublicHolidays(Request $request)
    {
        try {
            $today = now()->startOfDay();
            $endDate = $today->copy()->addDays(60);

            $holidays = PublicHoliday::whereDate('start_date', '>=', $today)
                ->whereDate('start_date', '<=', $endDate)
                ->orderBy('start_date', 'asc')
                ->get(['name', 'start_date', 'end_date'])
                ->map(function ($holiday) {
                    return [
                        'title' => $holiday->name,
                        'start' => \Carbon\Carbon::parse($holiday->start_date)->format('Y-m-d'),
                        'end' => \Carbon\Carbon::parse($holiday->end_date)->format('Y-m-d'),
                        'days_left' => now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($holiday->start_date)->startOfDay()),
                    ];
                });

            return response()->json([
                'message' => 'Upcoming public holidays retrieved successfully.',
                'data' => $holidays
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function leavesByStatus()
    {
        try {
            $userId = Auth::id();
            $currentYear = now()->year;

            $leaveStats = Leave::where('user_id', $userId)
                ->whereYear('start_date', $currentYear)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            return response()->json([
                'message' => 'Leave status statistics for current year retrieved successfully',
                'data' => $leaveStats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function leaveBalance()
    {
        try {
            $user = Auth::user();
            $userId = $user->id;
            $currentYear = now()->year;

            $response = [];

            // === Traitement pour "personal_leave" combiné avec "other"
            $customTypes = ['personal_leave', 'other'];

            $customBalance = LeavesBalance::where('user_id', $userId)
                ->sum('leave_day_limit');

            $customUsed = Leave::where('user_id', $userId)
                ->whereIn('leave_type', $customTypes)
                ->where('status', 'approved')
                ->whereYear('start_date', $currentYear)
                ->sum('effective_leave_days');

            $customRemaining = $customBalance - $customUsed;

            $response[] = [
                'leave_type' => 'personal_leave',
                'remaining' => round($customRemaining, 2)
            ];

            // === Traitement des types fixes selon le genre
            $fixedTypes = ['sick_leave'];

            if ($user->gender === 'male') {
                $fixedTypes[] = 'paternity_leave';
            } elseif ($user->gender === 'female') {
                $fixedTypes[] = 'maternity_leave';
            }

            $fixedLeaves = FixedLeaves::whereIn('leave_type', $fixedTypes)->get();

            foreach ($fixedTypes as $type) {
                $limit = optional($fixedLeaves->firstWhere('leave_type', $type))->max_days ?? 0;

                $used = Leave::where('user_id', $userId)
                    ->where('leave_type', $type)
                    ->where('status', 'approved')
                    ->whereYear('start_date', $currentYear)
                    ->sum('effective_leave_days');

                $remaining = $limit - $used;

                $response[] = [
                    'leave_type' => $type,
                    'remaining' => round($remaining, 2)
                ];
            }

            return response()->json([
                'message' => 'Leave balance retrieved successfully.',
                'data' => $response
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], 500);
        }
    }



    public function lastLeaveAddition()
    {
        try {
            $userId = Auth::id();

            $lastAddition = LeavesBalance::where('user_id', $userId)
                ->latest('created_at')
                ->first(['leave_day_limit', 'created_at']);

            if (!$lastAddition) {
                return response()->json([
                    'message' => 'No leave additions found for this user',
                    'data' => null
                ]);
            }

            return response()->json([
                'message' => 'Last leave addition retrieved successfully',
                'data' => [
                    'leave_day_limit' => $lastAddition->leave_day_limit,
                    'date' => $lastAddition->created_at->format('Y-m-d'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

public function getCalendarData()
{
    try {
        $userId = Auth::id();
        $currentYear = now()->year;

        // Récupérer les congés de l'utilisateur pour l'année en cours
        $userLeaves = Leave::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereYear('start_date', $currentYear)
            ->get(['start_date', 'end_date', 'leave_type'])
            ->map(function ($leave) {
                return [
                    'title' => ucfirst(str_replace('_', ' ', $leave->leave_type)),
                    'start' => \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d'),
                    'end' => \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d'),
                    'color' => $this->getLeaveTypeColor($leave->leave_type),
                ];
            });

        // Récupérer les jours fériés
        $publicHolidays = PublicHoliday::whereYear('start_date', $currentYear)
            ->get(['name', 'start_date', 'end_date'])
            ->map(function ($holiday) {
                return [
                    'title' => $holiday->name,
                    'start' => \Carbon\Carbon::parse($holiday->start_date)->format('Y-m-d'),
                    'end' => \Carbon\Carbon::parse($holiday->end_date)->format('Y-m-d'),
                    'color' => '#F4C430',  // Couleur fixe pour jours fériés
                ];
            });

        // Fusionner les deux (toujours des collections même si vides)
        $calendarData = collect($userLeaves)->merge($publicHolidays);

        return response()->json([
            'message' => 'Calendar data retrieved successfully',
            'data' => $calendarData
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'An unexpected error occurred.',
            'details' => $e->getMessage()
        ], 500);
    }
}




    // Fonction pour déterminer la couleur du type de congé
    private function getLeaveTypeColor($leaveType)
    {
        $colors = [
            'paternity_leave' => '#4CAF50',  // Vert pour congé paternité
            'maternity_leave' => '#E91E63',  // Jaune pour congé maternité
            'sick_leave' => '#F44336',       // Rouge pour congé maladie
            'personal_leave' => '#2196F3',   // Bleu pour congé personnel
            'other' => '#9E9E9E',            // Gris pour autre type
        ];

        return $colors[$leaveType] ?? '#000000';
    }


}
