<?php

namespace App\Http\Controllers;

use App\Models\Rental_photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Rental_photoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $photos = Rental_photo::all();

        return response()->json([
            'status' => true,
            'paths' => $photos
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rental_id = $request->rental_id;
        $numberofphotos = 0;

        foreach ($request->file('path') as $file) {
            $imagepath = random_int(99999, 999999999999999) . '.' . $file->getClientOriginalExtension();

            Storage::disk('public')->put('RentalPhotos/' . $imagepath, file_get_contents($file));

            Rental_photo::create([
                'path' => $imagepath,
                'rental_id' => $rental_id,
            ]);
            $numberofphotos++;
        }

        return response()->json([
            'status' => true,
            'message' => $numberofphotos . ' Photos have been created successfully',
        ], 200);
    }


    /**
     * Display the specified resource.
     */
    public function show(Rental_photo $rental_photo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Rental_photo $rental_photo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $ids)
    {

        $idsArray = explode(',', $ids);

        $deleted_photos = Rental_photo::whereIn('id', $idsArray)->get();

        $oneId = array_shift($idsArray);
        $rental_id = Rental_photo::where('id', $oneId)->value('rental_id');

        foreach ($deleted_photos as $photo) {
            Storage::disk('public')->delete('RentalPhotos/' . $photo->path);
            $photo->delete();
        }

        try {
            $files = $request->file('path');
            if (!$files) {
                return response()->json([
                    'status' => false,
                    'message' => 'No photos were provided',
                ], 422);
            }

            $numberofphotos = 0;
            foreach ($files  as $file) {
                $imagepath = random_int(99999, 999999999999999) . '.' . $file->getClientOriginalExtension();

                Storage::disk('public')->put('RentalPhotos/' . $imagepath, file_get_contents($file));

                Rental_photo::create([
                    'path' => $imagepath,
                    'rental_id' => $rental_id,
                ]);
                $numberofphotos++;
            }
            return response()->json([
                'status' => true,
                'message' => $numberofphotos . ' Photos have been updated successfully',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to upload photos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($ids)
    {
        $idsArray = explode(',', $ids);
        $deletedCount = 0;

        foreach ($idsArray as $id) {
            $deletephoto = Rental_photo::find($id);

            if (!$deletephoto) {
                continue;
            }

            $path = $deletephoto->path;
            $deletedphotopath = 'RentalPhotos/' . $path;

            if (Storage::disk('public')->exists($deletedphotopath)) {
                Storage::disk('public')->delete($deletedphotopath); //delete phot from local storage
                $deletephoto->delete(); //delete phot from the database
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            return response()->json([
                'status' => true,
                'message' => $deletedCount . ' photo(s) have been deleted successfully',
            ], 200);
        } else {
            return response()->json([
                'message' => 'The specified photos do not exist in the database or the local storage',
            ], 404);
        }
    }
}
