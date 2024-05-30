<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::with('role')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|regex:/^[6-9]\d{9}$/',
            'description' => 'required|string',
            'role_id' => 'required|exists:roles,id',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        try {
            $data = $request->all();
    
            if ($request->hasFile('profile_image')) {
                $imageName = time() . '.' . $request->profile_image->extension();
                $request->profile_image->move(public_path('profiles'), $imageName);
                $data['profile_image'] = 'profiles/' . $imageName;
            }
    
            $user = User::create($data);
    
            return response()->json($user, 201);
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
    
            return response()->json(['error' => 'An error occurred while creating the user. Please try again later.'], 500);
        }
    }
    

    public function show($id)
    {
        return response()->json(User::with('role')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'required|regex:/^[6-9]\d{9}$/',
            'description' => 'required|string',
            'role_id' => 'required|exists:roles,id',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        if ($request->hasFile('profile_image')) {
            // remove old image
            if ($user->profile_image) {
                if (file_exists($user->profile_image)) {
                    unlink($user->profile_image);
                }
            }
            $imageName = time() . '.' . $request->profile_image->extension();
            $request->profile_image->move(public_path('profiles'), $imageName);
            $data['profile_image'] = 'profiles/' . $imageName;
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->profile_image) {
            Storage::disk('public')->delete('profiles/'.$user->profile_image);
        }
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
