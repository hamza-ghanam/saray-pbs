<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as MYPDF;

readonly class PdfService
{
    public function __construct(
        private ?string $defaultDisk = null,
    ) {}

    /**
     * Render view into raw PDF bytes.
     */
    public function render(string $view, array $data = [], array $mpdfOverrides = []): string
    {
        $pdf = MYPDF::loadView($view, $data, [], [
            'instanceConfigurator' => function ($mpdf) use ($mpdfOverrides) {
                $this->configureMpdf($mpdf, $mpdfOverrides);
            }
        ]);

        return $pdf->output();
    }

    /**
     * Render + store PDF to disk.
     * Returns stored relative path.
     */
    public function store(string $view, array $data, string $path, ?string $disk = null, array $mpdfOverrides = []): string
    {
        $content = $this->render($view, $data, $mpdfOverrides);

        $disk = $disk ?: $this->disk();
        Storage::disk($disk)->put($path, $content);

        return $content;
    }

    /**
     * Render + store with auto-generated filename.
     * Returns ['path' => ..., 'file_name' => ...]
     */
    public function storeWithName(
        string $view,
        array $data,
        string $dir,
        string $fileName,
        ?string $disk = null,
        array $mpdfOverrides = []
    ): array {
        $dir = trim($dir, '/');
        $path = $dir . '/' . ltrim($fileName, '/');

        $storedPath = $this->store($view, $data, $path, $disk, $mpdfOverrides);

        return ['path' => $storedPath, 'file_name' => $fileName];
    }

    /**
     * Default mpdf config + overrides.
     */
    private function configureMpdf($mpdf, array $overrides = []): void
    {
        $base = (array) config('mypdf.mpdf', []);

        // overrides allow per-document tweaks
        $cfg = array_merge($base, $overrides);

        foreach ($cfg as $key => $value) {
            // only set if property exists (mpdf uses public properties)
            if (property_exists($mpdf, $key)) {
                $mpdf->{$key} = $value;
            }
        }
    }

    private function disk(): string
    {
        return $this->defaultDisk ?: (string) config('pdf.disk', 'local');
    }
}
