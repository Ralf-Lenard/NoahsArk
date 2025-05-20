<?php

namespace App\Http\Controllers;

use App\Models\AnimalProfile;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AnimalProfileController extends Controller
{

    public function show(AnimalProfile $animalProfile)
    {
        return Inertia::render('Users/AnimalProfile', [
            'animalProfile' => $animalProfile,
        ]);
    }

    public function index()
    {
        $animals = AnimalProfile::all();
        return Inertia::render('Staff/AnimalProfileList', [
            'animals' => $animals,
        ]);
    }

    public function showStaff(AnimalProfile $animalProfile)
    {
        return Inertia::render('Staff/AnimalProfile', [
            'animalProfile' => $animalProfile,
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'required|numeric',
            'breed' => 'required|string|max:255',
            'color' => 'required|string|max:255',
            'gender' => 'required|string|max:255',
            'species' => 'required|string|max:255',
            'birthdate' => 'nullable|date',
            'description' => 'required|string',
            'medical_records' => 'nullable|string',
            'profile_picture' => 'nullable|image|max:51200',
            'unique_id' => 'nullable|string',
        ]);

        $data = $request->only(['name', 'age', 'breed', 'description', 'medical_records', 'unique_id', 'color', 'gender', 'species', 'birthdate']);
        $profilePicturePath = null;
        $traccar_id = null;

        // Handle image upload
        if ($request->hasFile('profile_picture')) {
            $filename = time() . '_' . $request->file('profile_picture')->getClientOriginalName();
            $request->file('profile_picture')->move(public_path('animal_profiles'), $filename);
            $profilePicturePath = 'animal_profiles/' . $filename;
        }

        // Register device with Traccar if unique_id is provided
        if (!empty($data['unique_id'])) {
            $deviceName = $data['name'];
            $uniqueId = $data['unique_id'];

            $authToken = 'SDBGAiEAuXKbBqrFakmr0uly8jRpcAJU_qaIECknQ5ocrE7NPVQCIQDzFS-JPSxynucR5OC5LN9eOpbvpvJXUuUz3C2LI-Z9aXsidSI6MSwiZSI6IjIwMjUtMDUtMTlUMTY6MDA6MDAuMDAwKzAwOjAwIn0';
            $url = 'https://e420-2001-4452-35b-8d00-c090-e4f-ca68-b9e.ngrok-free.app/api/devices';

            $deviceData = [
                "name" => $deviceName,
                "uniqueId" => $uniqueId,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $authToken, // Fixed
                'Content-Type' => 'application/json',
            ])->post($url, $deviceData);

            if ($response->successful()) {
                $traccar_id = $response->json('id'); // Use json() instead of array access
            } else {
                return redirect()->back()->with('error', 'Failed to register device with Traccar.');
            }
        }

        // Create animal profile
        AnimalProfile::create([
            'name' => $data['name'],
            'age' => $data['age'],
            'breed' => $data['breed'],
            'gender' => $data['gender'],
            'color' => $data['color'],
            'species' => $data['species'],
            'birthdate' => $data['birthdate'],
            'description' => $data['description'],
            'medical_records' => $data['medical_records'],
            'device_id' => $data['unique_id'] ?? null,
            'traccar_id' => $traccar_id,
            'image' => $profilePicturePath,
        ]);

        return redirect()->route('animal-profiles.show')->with('success', 'Animal profile created successfully.');
    }




    public function update(Request $request, $id)
    {
        $animal = AnimalProfile::find($id);

        if (!$animal) {
            return redirect()->back()->with('error', 'Animal profile not found.');
        }

        // Validate request data
        $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'required|numeric',
            'breed' => 'required|string|max:255',
            'color' => 'required|string|max:255',
            'gender' => 'required|string|max:255',
            'species' => 'required|string|max:255',
            'birthdate' => 'nullable|date',
            'description' => 'required|string',
            'medical_records' => 'nullable|string|max:1000',
            'profile_picture' => 'nullable|image|max:51200',
            'unique_id' => 'nullable|string',
        ]);

        // Update Traccar device if unique_id changed and traccar_id exists
        if ($request->input('unique_id') && $request->input('unique_id') !== $animal->device_id && $animal->traccar_id) {
            $authToken = 'SDBGAiEAuXKbBqrFakmr0uly8jRpcAJU_qaIECknQ5ocrE7NPVQCIQDzFS-JPSxynucR5OC5LN9eOpbvpvJXUuUz3C2LI-Z9aXsidSI6MSwiZSI6IjIwMjUtMDUtMTlUMTY6MDA6MDAuMDAwKzAwOjAwIn0';
            $traccarUrl = 'https://e420-2001-4452-35b-8d00-c090-e4f-ca68-b9e.ngrok-free.app/api/devices/' . $animal->traccar_id;

            $traccarData = [
                'id' => $animal->traccar_id,
                'name' => $request->input('name'),
                'uniqueId' => $request->input('unique_id'),
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $authToken,
                'Content-Type' => 'application/json',
            ])->put($traccarUrl, $traccarData);

            if ($response->failed()) {
                return redirect()->back()->with('error', 'Failed to update device on Traccar.');
            }
        }

        // Update fields in the database
        $animal->name = $request->input('name');
        $animal->age = $request->input('age');
        $animal->breed = $request->input('breed');
        $animal->color = $request->input('color');
        $animal->gender = $request->input('gender');
        $animal->species = $request->input('species');
        $animal->birthdate = $request->input('birthdate');
        $animal->description = $request->input('description');
        $animal->medical_records = $request->input('medical_records');
        $animal->device_id = $request->input('unique_id');

        // Handle profile picture update
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if it exists
            if ($animal->image && file_exists(public_path($animal->image))) {
                unlink(public_path($animal->image));
            }

            // Save new profile picture
            $filename = time() . '_' . $request->file('profile_picture')->getClientOriginalName();
            $request->file('profile_picture')->move(public_path('animal_profiles'), $filename);
            $animal->image = 'animal_profiles/' . $filename;
        }

        $animal->save();

        return redirect()->back()->with('success', 'Animal profile and Traccar device updated successfully.');
    }

    public function destroy(AnimalProfile $animalProfile)
    {
        $animalProfile->delete();
        return back()->with('success', 'Animal profile deleted.');
    }

    public function markAsAdopted($id)
    {
        $animal = AnimalProfile::findOrFail($id);
        $animal->is_adopted = true; // Mark the animal as adopted
        $animal->save();

        return redirect()->route('animal-profiles.show')->with('success', 'Animal marked as adopted.');
    }

    public function AnimalLocation($device_id)
    {
        $authToken = 'RzBFAiAYSaSiBqxQfPjUEoS2O3SELPXdvzMepaQalvxe5xwIMwIhAMzxf4nHjNrSA_NmofT7gPCZzL0rJdwqHktmUiJqReKoeyJ1IjoxLCJlIjoiMjAyNS0wNS0xOFQxNjowMDowMC4wMDArMDA6MDAifQ';
        $locationUrl = 'https://720c-2001-4452-35b-8d00-a033-fa42-3b6a-1c4c.ngrok-free.app/api/positions?deviceId=' . $device_id;

        $response = Http::withHeaders([
            'Authorization' => $authToken,
        ])->get($locationUrl);

        if ($response->successful()) {
            $locationData = $response->json();

            // Return as Inertia page
            return Inertia::render('Staff/AnimalLocation', [
                'location' => $locationData,
                'device_id' => $device_id,
            ]);
        } else {
            return redirect()->route('animal-profiles.show')->withErrors('Unable to fetch location data.');
        }
    }
}
