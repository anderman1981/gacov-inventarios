<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Support\Documentation\ProjectDocumentationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ProjectCenterController extends Controller
{
    public function index(Request $request, ProjectDocumentationService $documentation): View
    {
        $documents = $documentation->documents();
        $activeDocument = $documentation->find((string) $request->query('doc', 'status'))
            ?? $documents->first();

        return view('super-admin.project.index', [
            'documents' => $documents,
            'activeDocument' => $activeDocument,
            'statusSummary' => $documentation->statusSummary(),
        ]);
    }
}
