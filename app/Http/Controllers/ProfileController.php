<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use PasswordValidationRules;

    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function updateInfo(Request $request)
    {
        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $user = auth()->user();
        $data = ['name' => $request->name];

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::delete($user->photo);
            }

            $file     = $request->file('photo');
            $filename = $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/users', $filename);
            $data['photo'] = 'public/users/' . $filename;
        }

        $user->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password:web'],
            'password'         => $this->passwordRules(),
        ]);

        auth()->user()->forceFill(['password' => Hash::make($request->password)])->save();

        return back()->with('success', 'Password changed successfully.');
    }
}
