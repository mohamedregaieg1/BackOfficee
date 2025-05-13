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
            $userId = Auth::id();
            $currentYear = now()->year;

            $customTypes = ['personal_leave', 'other'];
            $fixedTypes = ['sick_leave', 'maternity_leave', 'paternity_leave'];

            $response = [];

            $customBalances = LeavesBalance::where('user_id', $userId)->get();

            foreach ($customTypes as $type) {
                $balance = $customBalances->firstWhere('leave_type', $type);
                $limit = $balance ? $balance->leave_day_limit : 0;

                $used = Leave::where('user_id', $userId)
                    ->where('leave_type', $type)
                    ->where('status', 'approved')
                    ->whereYear('start_date', $currentYear)
                    ->sum('effective_leave_days');

                $remaining = max($limit - $used, 0);

                $response[] = [
                    'leave_type' => $type,
                    'remaining' => $remaining
                ];
            }

            $fixedLeaves = FixedLeaves::whereIn('leave_type', $fixedTypes)->get();

            foreach ($fixedTypes as $type) {
                $fixed = $fixedLeaves->firstWhere('leave_type', $type);
                $limit = $fixed ? $fixed->max_days : 0;

                $used = Leave::where('user_id', $userId)
                    ->where('leave_type', $type)
                    ->where('status', 'approved')
                    ->whereYear('start_date', $currentYear)
                    ->sum('effective_leave_days');

                $remaining = max($limit - $used, 0);

                $response[] = [
                    'leave_type' => $type,
                    'remaining' => $remaining
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
                        'color' => '#FF5733',  // Une couleur spécifique pour les jours fériés
                    ];
                });

            // Fusionner les données de congés et de jours fériés
            $calendarData = $userLeaves->merge($publicHolidays);

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
            'maternity_leave' => '#FFC107',  // Jaune pour congé maternité
            'sick_leave' => '#F44336',       // Rouge pour congé maladie
            'personal_leave' => '#2196F3',   // Bleu pour congé personnel
            'other' => '#9E9E9E',            // Gris pour autre type
        ];

        return $colors[$leaveType] ?? '#000000';
    }


}
