<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class DeckPdfController extends Controller
{
    private const CACHE_FILENAME = 'kingdom-vitals-overview.pdf';

    private const RENDER_LOCK_KEY = 'deck-pdf-render';

    private const RENDER_LOCK_TTL = 60;

    private const RENDER_LOCK_WAIT = 45;

    public function __invoke(Request $request): Response
    {
        $viewPath = resource_path('views/presentations/overview.blade.php');
        $cachePath = storage_path('app/public/decks/'.self::CACHE_FILENAME);
        $viewMtime = File::exists($viewPath) ? File::lastModified($viewPath) : 0;

        if ($this->cacheIsFresh($cachePath, $viewMtime)) {
            return $this->fileResponse($cachePath, $request->boolean('download'));
        }

        $lock = Cache::lock(self::RENDER_LOCK_KEY, self::RENDER_LOCK_TTL);

        try {
            $lock->block(self::RENDER_LOCK_WAIT);

            // Another worker may have just finished — re-check before rendering.
            if ($this->cacheIsFresh($cachePath, $viewMtime)) {
                return $this->fileResponse($cachePath, $request->boolean('download'));
            }

            File::ensureDirectoryExists(dirname($cachePath));

            $deckUrl = route('presentation.overview').'?print-pdf';

            $browsershot = Browsershot::url($deckUrl)
                ->ignoreHttpsErrors()
                ->format('A4')
                ->landscape()
                ->margins(0, 0, 0, 0)
                ->showBackground()
                ->waitUntilNetworkIdle()
                ->setDelay(1500)
                ->timeout(60);

            if ($nodeBinary = config('laravel-screenshot.browsershot.node_binary')) {
                $browsershot->setNodeBinary($nodeBinary);
            }

            if ($npmBinary = config('laravel-screenshot.browsershot.npm_binary')) {
                $browsershot->setNpmBinary($npmBinary);
            }

            if ($chromePath = config('laravel-screenshot.browsershot.chrome_path')) {
                $browsershot->setChromePath($chromePath);
            }

            $browsershot->save($cachePath);

            touch($cachePath, $viewMtime);
        } catch (Throwable $e) {
            Log::error('Deck PDF render failed', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return response('Unable to render the deck PDF right now. Please try again in a moment.', Response::HTTP_SERVICE_UNAVAILABLE);
        } finally {
            $lock->release();
        }

        return $this->fileResponse($cachePath, $request->boolean('download'));
    }

    private function cacheIsFresh(string $cachePath, int $viewMtime): bool
    {
        return File::exists($cachePath)
            && $viewMtime > 0
            && File::lastModified($cachePath) >= $viewMtime;
    }

    private function fileResponse(string $cachePath, bool $forceDownload): BinaryFileResponse
    {
        $disposition = $forceDownload ? 'attachment' : 'inline';

        return response()->file($cachePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.self::CACHE_FILENAME.'"',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
