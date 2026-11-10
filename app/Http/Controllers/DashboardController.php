<?php

namespace App\Http\Controllers;

use App\Models\Epreuve;
use App\Models\EpreuveActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $canViewSchoolStats = $user->can('dashboard.view_school_stats');

        return Inertia::render('AcademiaWelcome', [
            'title' => 'Espace Academia',
            'userName' => $user->name,
            'isAuthenticated' => true,
            'canSubmitExams' => $user->can('exams.submit'),
            'canReviewExams' => $user->can('exams.review'),
            'canManageExams' => $user->can('exams.manage'),
            'canManageUsers' => $user->can('users.manage'),
            'canManageReferentials' => $user->can('academic_referentials.manage'),
            'canViewSchoolStats' => $canViewSchoolStats,
            'statistics' => $canViewSchoolStats ? $this->schoolStatistics() : null,
        ]);
    }

    /**
     * @return array{
     *     activeUsers: int,
     *     exams: array{total: int, pending: int, published: int, rejected: int},
     *     activity: array{consultations: int, downloads: int}
     * }
     */
    private function schoolStatistics(): array
    {
        $examCounts = Epreuve::query()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $activityCounts = EpreuveActivity::query()
            ->selectRaw('action, COUNT(*) AS aggregate')
            ->whereIn('action', ['consultation', 'telechargement'])
            ->groupBy('action')
            ->pluck('aggregate', 'action');
        $allExamCounts = array_map('intval', $examCounts->all());

        return [
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'exams' => [
                'total' => array_sum($allExamCounts),
                'pending' => (int) $examCounts->get('pending', 0),
                'published' => (int) $examCounts->get('published', 0),
                'rejected' => (int) $examCounts->get('rejected', 0),
            ],
            'activity' => [
                'consultations' => (int) $activityCounts->get('consultation', 0),
                'downloads' => (int) $activityCounts->get('telechargement', 0),
            ],
        ];
    }
}
