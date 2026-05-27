<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard/Index', [
            'summary' => [
                'stations' => $this->countTable('stations'),
                'measurements' => $this->countTable('measurements'),
                'incidents' => $this->countTable('disaster_incidents'),
                'documents' => $this->countTable('documents'),
            ],
        ]);
    }

    private function countTable(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }
}
