<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SigningLink;
use App\Models\ReservationForm;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class SignedDocumentsController extends Controller
{

    public function download(Request $request, string $token)
    {
        $variant = $request->query('variant', 'latest'); // latest|original|signed

        $link = SigningLink::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (!$link) {
            return response()->json(['error' => 'Invalid link.'], Response::HTTP_NOT_FOUND);
        }

        // Recommended: allow download even after submit (expired),
        // but block if cancelled.
        if ($link->status === SigningLink::STATUS_CANCELLED) {
            return response()->json(['error' => 'Link is not valid.'], 410);
        }

        $doc = $link->documentable;

        if (!$doc) {
            return response()->json(['error' => 'Document not found.'], Response::HTTP_NOT_FOUND);
        }

        // ---- Resolve paths (generic via method-exists) ----
        $originalPath = method_exists($doc, 'getOriginalPdfPath') ? $doc->getOriginalPdfPath() : ($doc->file_path ?? null);
        $signedPath   = method_exists($doc, 'getSignedPdfPath')   ? $doc->getSignedPdfPath()   : ($doc->signed_file_path ?? null);

        $path = match ($variant) {
            'original' => $originalPath,
            'signed'   => $signedPath,
            default    => $signedPath ?: $originalPath, // latest
        };

        if (empty($path) || !Storage::disk('local')->exists($path)) {
            return response()->json(['error' => 'File not found.'], Response::HTTP_NOT_FOUND);
        }

        $fileName = method_exists($doc, 'getDownloadFileName')
            ? $doc->getDownloadFileName($variant)
            : $this->fallbackFileName($link, $variant);

        return Storage::disk('local')->download($path, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function fallbackFileName(SigningLink $link, string $variant): string
    {
        $prefix = strtoupper((string) $link->document_type);
        $suffix = $variant === 'signed' ? 'SIGNED' : ($variant === 'original' ? 'ORIGINAL' : 'LATEST');
        return "{$prefix}_{$suffix}.pdf";
    }
}
