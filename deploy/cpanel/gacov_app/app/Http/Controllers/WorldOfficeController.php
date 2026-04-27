<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\WorldOfficeTemplateExport;
use App\Support\WorldOffice\WorldOfficeExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class WorldOfficeController extends Controller
{
    public function __construct(
        private readonly WorldOfficeExportService $exportService,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('reports.worldoffice'), 403);

        return view('worldoffice.index', [
            'formats' => $this->exportService->formats(),
        ]);
    }

    public function download(Request $request, string $category, string $direction): BinaryFileResponse
    {
        abort_unless($request->user()?->can('reports.worldoffice'), 403);

        $format = $this->exportService->definition($category, $direction);

        return Excel::download(
            new WorldOfficeTemplateExport(
                sheetTitle: $format['sheet_title'],
                headings: $format['headings'],
                rows: $format['rows'],
            ),
            $format['filename'],
        );
    }
}
