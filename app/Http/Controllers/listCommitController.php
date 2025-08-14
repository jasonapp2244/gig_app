<?php

namespace App\Http\Controllers;

use App\Models\ListCommit; // Assuming you have a ListCommit model
use Illuminate\Http\Request;

class listCommitController extends Controller
{
    public function addListCommits(Request $request)
    {
        $request->validate([
            'list_id' => 'required|exists:list_stories,id',
            'commit_message' => 'required|string|max:255',
        ]);

        try {
            $listCommit = new ListCommit();
            $listCommit->user_id = auth()->id();
            $listCommit->list_id = $request->list_id;
            $listCommit->commit = $request->commit_message;
            $listCommit->status = true;
            $listCommit->save();
            return response()->json([
                'message' => 'Commit added successfully',
                'data' => [
                    'list_id' => $request->list_id,
                    'commit_message' => $request->commit_message,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error adding commit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getListCommits(Request $request, $listId)
    {
        $commits = ListCommit::where('list_id', $listId)->with('user')->get();

        return response()->json([
            'message' => 'List commits retrieved successfully',
            'data' => $commits
        ], 200);
    }


}
