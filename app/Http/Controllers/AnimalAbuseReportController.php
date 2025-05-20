<?php

namespace App\Http\Controllers;

use App\Events\AnimalAbuseStatusUpdated;
use App\Models\AnimalAbuseReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AnimalAbuseReportController extends Controller
{
 
    public function index()
    {

        $animalAbuseReports = AnimalAbuseReport::with(['user'])->get();
      
        return Inertia::render('Staff/AnimalAbuseReport', [
            'animalAbuseReports' => $animalAbuseReports,
        ]);
    }

    public function indexUser()
    {
        return Inertia::render('Users/AnimalAbuseReport');
    }


    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:2000',
            'photos.*' => 'nullable|image|max:51200',
            'videos.*' => 'nullable|file|mimetypes:video/mp4,video/avi,video/mpeg|max:51200',
        ]);

        $photos = [];
        $videos = [];

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filename = time() . '_' . $photo->getClientOriginalName();
                $photo->move(public_path('abuse_photos'), $filename);
                $photos[] = 'abuse_photos/' . $filename;
            }
        }

        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $video) {
                $filename = time() . '_' . $video->getClientOriginalName();
                $video->move(public_path('abuse_videos'), $filename);
                $videos[] = 'abuse_videos/' . $filename;
            }
        }

        AnimalAbuseReport::create([
            'user_id' => Auth::id(),
            'description' => $request->description,
            'photos' => $photos,
            'videos' => $videos,
        ]);

        return redirect()->route('index.animal-abuse')->with('success', 'Animal abuse report submitted.');
    }

    public function updateStatus(Request $request, AnimalAbuseReport $animalAbuseReport)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'rejection_reason' => 'nullable|string|required_if:status,rejected',
        ]);
    
        $data = [
            'status' => $request->status,
            'rejection_reason' => $request->status === 'rejected' ? $request->rejection_reason : null,
        ];
    
        $animalAbuseReport->update($data);
    
    
        // Fire real-time event
        event(new AnimalAbuseStatusUpdated($animalAbuseReport));
    
        return back()->with('success', 'Animal abuse report status updated.');
    }
    

    

 
}
