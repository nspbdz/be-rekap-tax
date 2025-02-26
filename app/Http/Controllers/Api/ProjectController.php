<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? null;

        $query = Project::query()
        ->select('projects.*') // Memilih semua kolom dari projects
        ->when($request->filled('project_name') || $request->filled('project_location'), function ($q) use ($request) {
            $q->where(function ($query) use ($request) {
                if ($request->filled('project_name')) {
                    $query->where('project_name', 'like', '%' . $request->project_name . '%');
                }
                if ($request->filled('project_location')) {
                    $query->orWhere('project_location', 'like', '%' . $request->project_location . '%');
                }
            });
        })
        ->groupBy('id') // Hindari duplikasi
        ->orderByDesc('created_at'); // Urutkan berdasarkan tanggal terbaru

        return response()->json($query->paginate($request->get('per_page', $per_page)));
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'project_location' => 'required|string',
                'project_name' => 'required|string',
            ]);

            $project = Project::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully',
                'data' => $project
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing project.
     */
    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'project_location' => 'sometimes|string',
                'project_name' => 'sometimes|string',
            ]);

            $project = Project::findOrFail($request->id);
            $project->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully',
                'data' => $project
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a project (soft delete).
     */
    public function destroy($id)
    {
        try {
            $project = Project::findOrFail($id);
            $project->delete();

            return response()->json([
                'success' => true,
                'message' => 'Project deleted successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete project',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
