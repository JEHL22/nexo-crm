<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Almacenamiento de adjuntos de acuerdos (contratos de clientes).
 *
 * Los archivos viven en el disco 'local' (storage/app/private) — NUNCA en
 * public/, porque contienen datos de clientes y no deben ser accesibles por
 * URL directa sin autorización. La descarga pasa por
 * AgreementAttachmentController, que valida rol/ownership contra la venta.
 *
 * Las rutas relativas guardadas en sales.attachment_paths mantienen el
 * formato histórico 'agreement-attachments/<archivo>'.
 */
class AgreementAttachmentStorage
{
    private const DISK = 'local';

    private const DIRECTORY = 'agreement-attachments';

    /**
     * Guarda los archivos subidos y devuelve sus rutas relativas.
     * Si algo falla a medio camino, elimina lo ya guardado y relanza.
     *
     * @param  array<int, UploadedFile|null>  $files
     * @return array<int, string>
     */
    public static function store(array $files): array
    {
        $paths = [];

        try {
            foreach ($files as $file) {
                if (! $file) {
                    continue;
                }

                $filename = now()->format('YmdHis').'-'.Str::uuid().'.'.$file->getClientOriginalExtension();
                $file->storeAs(self::DIRECTORY, $filename, self::DISK);
                $paths[] = self::DIRECTORY.'/'.$filename;
            }
        } catch (\Throwable $e) {
            self::delete($paths);
            throw $e;
        }

        return $paths;
    }

    /**
     * @param  array<int, string>  $paths  rutas relativas tipo 'agreement-attachments/x.pdf'
     */
    public static function delete(array $paths): void
    {
        foreach ($paths as $path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public static function exists(string $path): bool
    {
        return Storage::disk(self::DISK)->exists($path);
    }

    /**
     * Respuesta inline (el visor PDF/imágenes del CRM la consume en iframe).
     */
    public static function response(string $path): StreamedResponse
    {
        return Storage::disk(self::DISK)->response($path);
    }
}
