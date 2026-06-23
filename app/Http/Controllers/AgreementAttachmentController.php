<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Support\AgreementAttachmentStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgreementAttachmentController extends Controller
{
    /**
     * Sirve un adjunto de acuerdo verificando que el usuario tenga acceso a
     * la venta. El filename se valida contra sales.attachment_paths, así que
     * no hay forma de pedir un archivo de otra venta ni de recorrer rutas.
     */
    public function show(Request $request, Sale $sale, string $filename): StreamedResponse
    {
        abort_unless($this->userCanViewSaleAttachments($request, $sale), 403);

        $relativePath = 'agreement-attachments/'.$filename;

        abort_unless(in_array($relativePath, $sale->attachment_paths ?? [], true), 404);
        abort_unless(AgreementAttachmentStorage::exists($relativePath), 404);

        return AgreementAttachmentStorage::response($relativePath);
    }

    private function userCanViewSaleAttachments(Request $request, Sale $sale): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        // Mismos accesos que las vistas que muestran adjuntos: supervisor
        // dueño del acuerdo, ejecutivo que lo registró, y los roles de
        // soporte que gestionan ventas de todo el sistema.
        return $sale->supervisor_user_id === $user->id
            || $sale->executive_user_id === $user->id
            || $user->hasAnyRole(['Mesa de Control', 'Gerencia', 'Postventa', 'Administrador']);
    }
}
