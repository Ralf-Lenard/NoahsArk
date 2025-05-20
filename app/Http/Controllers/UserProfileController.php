<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class UserProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
    
        if ($user->usertype === 'user') {
            return Inertia::render('Users/Profile', [
                'user' => $user
            ]);
        } else {
            return Inertia::render('Staff/Profile', [
                'user' => $user
            ]);
        }
    }
    

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'address' => 'nullable|string|max:255',
            'age' => 'nullable|integer|min:0',
            'gender' => 'nullable|string|in:male,female,other',
            'civil_status' => 'nullable|string|max:50',
            'phone_number' => 'nullable|digits:11',
            'profile_photo' => 'nullable|image|max:51200',
        ]);

        // Handle profile photo upload manually to public folder
        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo && file_exists(public_path($user->profile_photo))) {
                unlink(public_path($user->profile_photo));
            }

            $filename = time() . '_' . $request->file('profile_photo')->getClientOriginalName();
            $request->file('profile_photo')->move(public_path('user_profiles'), $filename);
            $validated['profile_photo'] = 'user_profiles/' . $filename;
        }

        $user->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }
}
