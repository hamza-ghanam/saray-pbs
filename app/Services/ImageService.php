<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImageService
{
    /**
     * Stream a local file with HTTP cache validators (ETag / Last-Modified).
     *
     * @param  Request $request
     * @param  string  $path     Relative path on local disk
     * @param  bool    $inline   Whether to display inline or force download
     * @return Response|BinaryFileResponse
     */
    public function streamImage(
        Request $request,
        string $path,
        bool $inline = true
    ) {
        if (!Storage::disk('local')->exists($path)) {
            throw new NotFoundHttpException('File not found.');
        }

        $fullPath = Storage::disk('local')->path($path);

        $lastModified = gmdate('D, d M Y H:i:s', filemtime($fullPath)) . ' GMT';
        $eTag = '"' . md5_file($fullPath) . '"';

        // Client cache validation
        if (
            $request->headers->get('if-none-match') === $eTag ||
            $request->headers->get('if-modified-since') === $lastModified
        ) {
            return response('', ResponseAlias::HTTP_NOT_MODIFIED)
                ->withHeaders($this->noCacheHeaders($eTag, $lastModified));
        }

        return response()->file($fullPath, array_merge(
            [
                'Content-Disposition' => ($inline ? 'inline' : 'attachment')
                    . '; filename="' . basename($path) . '"',
            ],
            $this->noCacheHeaders($eTag, $lastModified)
        ));
    }

    /**
     * Standard no-cache headers with validators.
     */
    private function noCacheHeaders(string $eTag, string $lastModified): array
    {
        return [
            'Cache-Control'  => 'no-cache, must-revalidate, max-age=0, proxy-revalidate',
            'Pragma'         => 'no-cache',
            'Expires'        => '0',
            'ETag'           => $eTag,
            'Last-Modified'  => $lastModified,
        ];
    }
}
