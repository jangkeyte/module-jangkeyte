<?php

namespace JangKeyte\Http\Controllers\Media;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use JangKeyte\Services\MediaService;

class MediaController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService
    ) {
        $this->middleware('permission:browse-media')->only(['index']);
        $this->middleware('permission:add-media')->only(['createFolder', 'upload']);
        $this->middleware('permission:delete-media')->only(['destroy']);
    }

    public function index(Request $request): View|JsonResponse
    {
        $rawFolder = $request->query('folder', '');
        if (! is_string($rawFolder) && $rawFolder !== null) {
            return $this->invalidPathResponse($request, __('Invalid folder path.'));
        }

        $folder = (string) ($rawFolder ?? '');

        try {
            $listing = $this->mediaService->listDirectory($folder);
        } catch (InvalidArgumentException $e) {
            return $this->invalidPathResponse($request, $e->getMessage());
        }

        if ($this->wantsJsonResponse($request)) {
            return response()->json($listing);
        }

        return view('JangKeyte::media.index', [
            'listing' => $listing,
            'currentFolder' => $listing['current_path'],
            'allowedExtensions' => config('jangkeyte.media.allowed_extensions', []),
            'maxSizeKb' => (int) config('jangkeyte.media.max_size_kb', 5120),
        ]);
    }

    public function createFolder(Request $request): RedirectResponse|JsonResponse
    {
        $rawParent = $request->input('parent_path', '');
        $rawFolder = $request->input('folder_name', '');

        if ((! is_string($rawParent) && $rawParent !== null) || (! is_string($rawFolder) && $rawFolder !== null)) {
            return $this->invalidPathResponse($request, __('Invalid folder parameters.'));
        }

        $validated = $request->validate([
            'parent_path' => ['nullable', 'string', 'max:255'],
            'folder_name' => ['required', 'string', 'max:100'],
        ]);

        $parentPath = (string) ($validated['parent_path'] ?? '');

        try {
            $createdPath = $this->mediaService->createDirectory($parentPath, $validated['folder_name']);
        } catch (InvalidArgumentException $e) {
            return $this->invalidPathResponse($request, $e->getMessage());
        }

        $message = __('Folder created successfully.');

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'path' => $createdPath,
                'message' => $message,
            ], 201);
        }

        return redirect()
            ->route('admin.media.index', array_filter(['folder' => $parentPath], static fn (string $v): bool => $v !== ''))
            ->with('success', $message);
    }

    public function upload(Request $request): RedirectResponse|JsonResponse
    {
        $rawTarget = $request->input('target_path', '');
        if (! is_string($rawTarget) && $rawTarget !== null) {
            return $this->invalidPathResponse($request, __('Invalid target path.'));
        }

        $rawFiles = $request->file('files');

        if ($rawFiles instanceof UploadedFile) {
            $request->validate([
                'target_path' => ['nullable', 'string', 'max:255'],
                'files' => ['required', 'file'],
            ]);
            $files = [$rawFiles];
        } else {
            $request->validate([
                'target_path' => ['nullable', 'string', 'max:255'],
                'files' => ['required', 'array', 'min:1'],
                'files.*' => ['required', 'file'],
            ]);
            $files = is_array($rawFiles) ? $rawFiles : [];
        }

        $targetPath = (string) ($rawTarget ?? '');

        try {
            $uploaded = $this->mediaService->uploadFiles($targetPath, $files);
        } catch (InvalidArgumentException $e) {
            return $this->invalidPathResponse($request, $e->getMessage());
        }

        $message = __('Files uploaded successfully.');

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'files' => $uploaded,
                'message' => $message,
            ], 201);
        }

        return redirect()
            ->route('admin.media.index', array_filter(['folder' => $targetPath], static fn (string $v): bool => $v !== ''))
            ->with('success', $message);
    }

    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $rawPath = $request->input('path', '');
        if (! is_string($rawPath) && $rawPath !== null) {
            return $this->invalidPathResponse($request, __('Invalid media path.'));
        }

        $targetPath = (string) ($rawPath ?? '');

        try {
            $deleted = $this->mediaService->deleteItem($targetPath);
        } catch (InvalidArgumentException $e) {
            return $this->invalidPathResponse($request, $e->getMessage());
        }

        if (! $deleted) {
            $notFoundMessage = __('Media item not found.');

            if ($this->wantsJsonResponse($request)) {
                return response()->json([
                    'deleted' => false,
                    'message' => $notFoundMessage,
                ], 404);
            }

            return redirect()
                ->back()
                ->with('error', $notFoundMessage);
        }

        $message = __('Media item deleted successfully.');

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'deleted' => true,
                'message' => $message,
            ]);
        }

        $normalized = trim(str_replace('\\', '/', $targetPath), '/');
        $parentFolder = dirname($normalized);
        $parentFolder = ($parentFolder === '.' || $parentFolder === '/') ? '' : $parentFolder;

        return redirect()
            ->route('admin.media.index', array_filter(['folder' => $parentFolder], static fn (string $v): bool => $v !== ''))
            ->with('success', $message);
    }

    private function wantsJsonResponse(Request $request): bool
    {
        return $request->boolean('json') || $request->expectsJson();
    }

    private function invalidPathResponse(Request $request, string $message): JsonResponse
    {
        if ($this->wantsJsonResponse($request)) {
            return response()->json(['message' => $message], 422);
        }

        abort(422, $message);
    }
}
