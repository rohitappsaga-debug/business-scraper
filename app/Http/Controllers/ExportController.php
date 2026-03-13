<?php

namespace App\Http\Controllers;

use App\Exports\BusinessesExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    /**
     * Download businesses as a CSV file.
     */
    public function csv(Request $request): BinaryFileResponse
    {
        $filters = $this->resolveFilters($request);

        return Excel::download(
            new BusinessesExport($filters),
            'businesses.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    /**
     * Download businesses as an Excel file.
     */
    public function excel(Request $request): BinaryFileResponse
    {
        $filters = $this->resolveFilters($request);

        return Excel::download(
            new BusinessesExport($filters),
            'businesses.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFilters(Request $request): array
    {
        return array_filter([
            'location' => $request->query('location'),
            'category' => $request->query('category'),
            'min_rating' => $request->query('min_rating'),
            'has_email' => $request->boolean('has_email') ? true : null,
        ]);
    }
}
