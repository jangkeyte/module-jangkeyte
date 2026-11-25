<?php

namespace JangKeyte\Http\Controllers\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use JangKeyte\Services\FileUploadService;

class ProfileController extends Controller
{
    public function __construct(protected FileUploadService $fileUploadService) {}

    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('JangKeyte::auth.profile', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:225'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->hasFile('photo')) {
            $user->photo = $this->fileUploadService->uploadPublic(
                $request->file('photo'),
                'users',
                $user->photo
            );
        }

        $user->save();

        return redirect()->back()->with('success', __('Cập nhật hồ sơ cá nhân thành công.'));
    }
}
