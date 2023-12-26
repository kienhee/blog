<?php

namespace App\Http\Controllers\Admin\Project;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $result = Project::query();

        if ($request->has('keywords') && $request->keywords != null) {
            $result->where('name', 'like', '%' . $request->keywords . '%');
        }

        if ($request->has('category_id') && $request->category_id != null) {
            $result->where('category_project_id',  $request->category_id);
        }

        if ($request->has('sort') && $request->sort != null) {
            $result->orderBy('created_at', $request->sort);
        } else {
            $result->orderBy('created_at', 'desc');
        }
        if ($request->has('status') && $request->status != null && $request->status == 'active') {
            $result->where('deleted_at', "=", null);
        } elseif ($request->has('status') && $request->status != null && $request->status == 'inactive') {
            $result->onlyTrashed();
        } else {
            $result->withTrashed();
        }

        $projectss = $result->paginate(10);
        return view('admin.project.index', compact('projects'));
    }

    public function add()
    {
        return view('admin.project.add');
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'cover' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'title' => 'required|string|unique:projects,title,max:255',
            'slug' => 'required|string|unique:projects,slug|max:255',
            'category_project_id' => 'required|integer',
            'description' => 'required|string',
            'content' => 'required|string',
            'website' => 'nullable|url',
        ]);

        if ($request->hasFile('cover')) {
            $file = $request->file('cover');
            $filename = $file->hashName();
            $path = $file->storePubliclyAs('public/photos/1/project', $filename);
            $url = Storage::url($path);
            $validate['cover'] = $url;
        }

        $check = Project::insert($validate);
        if ($check) {
            return back()->with('msgSuccess', 'Successfully created');
        }
        return back()->with('msgError', 'Failed to add project!');
    }

    public function edit(Project $project)
    {
        return view('admin.project.edit', compact('project'));
    }

    public function update(Request $request, $id)
    {
        $validate = $request->validate([
            'cover' => 'nullable',
            'title' => 'required|string|max:255|unique:projects,title,' . $id,
            'slug' => 'required|string|max:255|unique:projects,slug,' . $id,
            'category_project_id' => 'required|integer',
            'description' => 'required|string',
            'content' => 'required|string',
            'website' => 'nullable|url',
        ]);

        if ($request->hasFile('cover')) {
            $file = $request->file('cover');
            $filename = $file->hashName();
            $path = $file->storePubliclyAs('public/photos/1/project', $filename);
            $url = Storage::url($path);
            $validate['cover'] = $url;
        }

        $check = Project::where('id', $id)->update($validate);
        if ($check) {
            return back()->with('msgSuccess', 'Update successful');
        }
        return back()->with('msgError', 'Update failed!');
    }

    public function softDelete($id)
    {
        $check = Project::destroy($id);
        if ($check) {
            return back()->with('msgSuccess', 'Change status successful');
        }
        return back()->with('msgError', 'Change status failed!');
    }

    public function restore($id)
    {
        $check = Project::onlyTrashed()->where('id', $id)->restore();
        if ($check) {
            return back()->with('msgSuccess', 'Restore successful');
        }
        return back()->with('msgError', 'Restore failed!');
    }

    public function forceDelete($id)
    {
        $check = Project::onlyTrashed()->where('id', $id)->forceDelete();
        if ($check) {
            return back()->with('msgSuccess', 'Delete successful');
        }
        return back()->with('msgError', 'Delete failed!');
    }
}
