<?php

namespace App\Http\Controllers;

use App\Models\ListCategory;
use Illuminate\Http\Request;
use Symfony\Component\Console\Command\ListCommand;

class ListCategoryController extends Controller
{


    public function getListCategory(Request $request)
    {
        $user = auth()->user();
        try {
            $lists = ListCategory::with('user')
                ->where('user_id', $user->id)
                ->get();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving categories',
                'error' => $e->getMessage()
            ], 500);
        }
        $lists = ListCategory::where('user_id', $user->id)
            ->get();
        return response()->json([
            'message' => 'List stories retrieved successfully',
            'data'    => $lists
        ], 200);
    }



    public function deleteCategory($id)
    {
        try {
            $category = ListCategory::findOrFail($id);
            $category->delete();

            return response()->json([
                'message' => 'Category deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
