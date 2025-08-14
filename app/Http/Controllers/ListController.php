<?php

namespace App\Http\Controllers;

use App\Models\ListStory;
use App\Models\ListCategory;
use App\Models\ListImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListController extends Controller
{

  public function getList(Request $request)
    {
        $user = auth()->user();
        $lists = ListStory::with('category', 'images')
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'message' => 'List stories retrieved successfully',
            'data'    => $lists
        ], 200);
    }

    public function addList(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'required|string',
            'old_price'    => 'nullable|numeric',
            'new_price'    => 'nullable|numeric',
            'location'     => 'required|string',
            'description'  => 'nullable|string',
            'condition'    => 'required|string',
            'images.*'     => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);


        if (is_numeric($request->category)) {
            $categoryId = $request->category;
        } else {
            $category = ListCategory::firstOrCreate(
                [
                    'category' => $request->category
                ],
                [
                    'user_id' => auth()->id()
                ]
            );
            $categoryId = $category->id;
        }

        $list = ListStory::create([
            'user_id'     => auth()->id(),
            'category_id' => $categoryId,
            'title'       => $request->title,
            'old_price'   => $request->old_price,
            'new_price'   => $request->new_price,
            'location'    => $request->location,
            'description' => $request->description,
            'condition'   => $request->condition,

        ]);


        if ($request->hasFile('images')) {
            $existingHashes = ListImage::where('list_id', $list->id)->pluck('hash_name')->toArray();

            foreach ($request->file('images') as $image) {
                $hash = md5_file($image->getRealPath());

                if (!in_array($hash, $existingHashes) && $list->images()->count() < 3) {
                    $path = $image->store('list_images', 'public');

                    ListImage::create([
                        'list_id'    => $list->id,
                        'image_name' => $image->getClientOriginalName(),
                        'path'       => $path,
                        'hash_name'  => $hash
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'List story added successfully',
            'data'    => $list->load('category', 'images')
        ], 200);
    }

    public function updateList(Request $request, $id)
    {
        // dd($request->toArray());
        $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'required|string',
            'old_price'    => 'nullable|numeric',
            'new_price'    => 'nullable|numeric',
            'location'     => 'required|string',
            'description'  => 'nullable|string',
            'condition'    => 'required|string',
            'images.*'     => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);


        $list = ListStory::findOrFail($id);


        if (is_numeric($request->category)) {
            $categoryId = $request->category;
        } else {
            $category = ListCategory::firstOrCreate(
                ['category' => $request->category],
                ['user_id' => auth()->id()]
            );
            $categoryId = $category->id;
        }


        $list->update([
            'category_id' => $categoryId,
            'title'       => $request->title,
            'old_price'   => $request->old_price,
            'new_price'   => $request->new_price,
            'location'    => $request->location,
            'description' => $request->description,
            'condition'   => $request->condition,
        ]);


        if ($request->hasFile('images')) {
            $existingHashes = ListImage::where('list_id', $list->id)->pluck('hash_name')->toArray();

            foreach ($request->file('images') as $image) {
                $hash = md5_file($image->getRealPath());

                if (!in_array($hash, $existingHashes) && $list->images()->count() < 3) {
                    $path = $image->store('list_images', 'public');

                    ListImage::create([
                        'list_id'    => $list->id,
                        'image_name' => $image->getClientOriginalName(),
                        'path'       => $path,
                        'hash_name'  => $hash
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'List story updated successfully',
            'data'    => $list->load('category', 'images')
        ], 200);
    }

    public function deleteList(Request $request, $id)
    {
        $list = ListStory::findOrFail($id);

        // Delete associated images
        foreach ($list->images as $image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }

        // Delete the list story
        $list->delete();

        return response()->json([
            'message' => 'List story deleted successfully'
        ], 200);
    }

}
