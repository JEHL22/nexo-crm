<?php

namespace App\Http\Controllers;

use App\Models\PromoDocument;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ExecutivePromoDocumentController extends Controller
{
    public function index(): View
    {
        return $this->renderLibrary(
            'Panel Ejecutivo',
            'Consulta todas las promociones activas desde una sola pantalla y visualiza el PDF sin salir del módulo.'
        );
    }

    public function supervisorIndex(): View
    {
        return $this->renderLibrary(
            'Panel Supervisor',
            'Consulta las promociones activas que maneja tu equipo y revisa los PDFs desde una vista general.'
        );
    }

    private function renderLibrary(string $panelLabel, string $description): View
    {
        $documents = Schema::hasTable('promo_documents')
            ? PromoDocument::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(['id', 'badge', 'title', 'pdf_path', 'sort_order'])
            : collect();

        $selectedDocument = $documents->first();

        return view('executive-promotions.index', [
            'documents' => $documents,
            'selectedDocument' => $selectedDocument,
            'tableMissing' => !Schema::hasTable('promo_documents'),
            'panelLabel' => $panelLabel,
            'panelDescription' => $description,
        ]);
    }
}
