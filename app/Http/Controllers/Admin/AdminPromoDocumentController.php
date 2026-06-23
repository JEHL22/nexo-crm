<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminPromoDocumentController extends Controller
{
    public function index(): View
    {
        if (!Schema::hasTable('promo_documents')) {
            return view('admin.promotions.index', [
                'documents' => PromoDocument::query()->whereRaw('1 = 0')->paginate(10),
                'totals' => [
                    'documents' => 0,
                    'active' => 0,
                ],
                'tableMissing' => true,
            ]);
        }

        $documents = PromoDocument::query()
            ->with('sender:id,name')
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->paginate(10);

        return view('admin.promotions.index', [
            'documents' => $documents,
            'totals' => [
                'documents' => PromoDocument::count(),
                'active' => PromoDocument::where('is_active', true)->count(),
            ],
            'tableMissing' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (!Schema::hasTable('promo_documents')) {
            return back()->withErrors([
                'promotion' => 'La tabla de promociones PDF no existe todavía. Ejecuta las migraciones pendientes antes de guardar.',
            ]);
        }

        $validated = $request->validate([
            'badge' => ['nullable', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:160'],
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $pdfPath = $this->storePdf($request->file('pdf_file'));

        PromoDocument::create([
            'sender_user_id' => $request->user()?->id,
            'badge' => filled($validated['badge'] ?? null) ? trim((string) $validated['badge']) : null,
            'title' => trim((string) $validated['title']),
            'pdf_path' => $pdfPath,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('success', 'Promoción PDF creada correctamente.');
    }

    public function update(Request $request, PromoDocument $promotion): RedirectResponse
    {
        if (!Schema::hasTable('promo_documents')) {
            return back()->withErrors([
                'promotion' => 'La tabla de promociones PDF no existe todavía. Ejecuta las migraciones pendientes antes de actualizar.',
            ]);
        }

        $validated = $request->validate([
            'badge' => ['nullable', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:160'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = [
            'badge' => filled($validated['badge'] ?? null) ? trim((string) $validated['badge']) : null,
            'title' => trim((string) $validated['title']),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if ($request->hasFile('pdf_file')) {
            $data['pdf_path'] = $this->storePdf($request->file('pdf_file'));
        }

        $promotion->update($data);

        return back()->with('success', 'Promoción PDF actualizada correctamente.');
    }

    private function storePdf($file): string
    {
        $directory = public_path('promo-documents');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = now()->format('YmdHis').'-'.Str::uuid().'.pdf';
        $file->move($directory, $filename);

        return 'promo-documents/'.$filename;
    }
}
