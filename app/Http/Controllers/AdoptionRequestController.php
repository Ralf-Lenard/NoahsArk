<?php

namespace App\Http\Controllers;

use App\Events\AdoptionStatusUpdated;
use App\Models\AdoptionRequest;
use App\Models\AnimalProfile;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdoptionRequestController extends Controller
{

    public function indexUser(Request $request)
    {
        $search = $request->input('search');

        $animals = AnimalProfile::where('is_adopted', false)
            ->where('is_temporarily_adopted', false)
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('breed', 'like', "%{$search}%");
                });
            })
            ->paginate(6)
            ->appends(['search' => $search]);

        return Inertia::render('Users/Home', [
            'animals' => $animals,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }



    public function store(Request $request)
    {
        $user = Auth::user();

        $missing = [];
        if (empty($user->address)) $missing[] = 'address';
        if (empty($user->phone_number)) $missing[] = 'phone number';
        if (empty($user->age)) $missing[] = 'age';
        if (empty($user->gender)) $missing[] = 'gender';
        if (empty($user->civil_status)) $missing[] = 'civil status';

        if ($missing) {
            return redirect()->route('profile.edit')->with('error', 'Please complete your profile: ' . implode(' and ', $missing));
        }


        $request->validate([
            'animal_profile_id' => 'required|exists:animal_profiles,id',
            'question1' => 'required|string|max:1000',
            'question2' => 'required|string|max:1000',
            'question3' => 'required|string|max:1000',
            'valid_id' => 'required|file|mimes:jpg,jpeg,png,pdf|max:30720',
            'selfie_with_id' => 'required|file|mimes:jpg,jpeg,png|max:30720',
        ]);

        $data = $request->only([
            'animal_profile_id',
            'question1',
            'question2',
            'question3',
        ]);

        $data['user_id'] = $user->id;
        $data['status'] = 'pending';

        if ($request->hasFile('valid_id')) {
            $file = $request->file('valid_id');
            $filename = time() . '_valid_id_' . $file->getClientOriginalName();
            $file->move(public_path('adoption_files'), $filename);
            $data['valid_id'] = 'adoption_files/' . $filename;
        }

        if ($request->hasFile('selfie_with_id')) {
            $file = $request->file('selfie_with_id');
            $filename = time() . '_selfie_' . $file->getClientOriginalName();
            $file->move(public_path('adoption_files'), $filename);
            $data['selfie_with_id'] = 'adoption_files/' . $filename;
        }

        // Create the adoption request
        $adoption = AdoptionRequest::create($data);

        // Mark the animal as temporarily adopted
        AnimalProfile::where('id', $request->animal_profile_id)
            ->update(['is_temporarily_adopted' => true]);

        return redirect()->route('user.home')->with('success', 'Adoption request submitted!');
    }

    public function index()
    {
        // Eager load user and animal related to the adoption request
        $adoptionRequests = AdoptionRequest::with(['user', 'animal_profile'])->get();

        // Return the data to the Inertia view
        return Inertia::render('Staff/AdoptionRequests', [
            'adoptionRequests' => $adoptionRequests,
        ]);
    }



    
    public function updateStatus(Request $request, AdoptionRequest $adoptionRequest)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'rejection_reason' => 'nullable|string|required_if:status,rejected',
        ]);
    
        $data = [
            'status' => $request->status,
        ];
    
        if ($request->status === 'rejected') {
            $data['rejection_reason'] = $request->rejection_reason;
    
            AnimalProfile::where('id', $adoptionRequest->animal_profile_id)
                ->update(['is_temporarily_adopted' => false]);
        } else {
            $data['rejection_reason'] = null;
        }
    
        if ($request->status === 'approved') {
            AnimalProfile::where('id', $adoptionRequest->animal_profile_id)
                ->update([
                    'is_adopted' => true,
                    'is_temporarily_adopted' => false,
                ]);
        }
    
        $adoptionRequest->update($data);
    
    
        // Fire the event
        event(new AdoptionStatusUpdated($adoptionRequest));
    
        return back()->with('success', 'Adoption status updated.');
    }
    
}
