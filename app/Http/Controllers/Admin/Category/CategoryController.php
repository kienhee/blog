<?php

namespace App\Http\Controllers\Admin\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $result = Category::query();

        if ($request->has('keywords') && $request->keywords != null) {
            $result->where('name', 'like', '%' . $request->keywords . '%');
        }

        if ($request->has('sort') && $request->sort != null) {
            $result->orderBy('created_at', $request->sort);
        } else {
            $result->orderBy('created_at', 'desc');
        }

        if ($request->has('status') && $request->status != null && $request->status == 'active') {
            $result->where('deleted_at', '=', null);
        } elseif ($request->has('status') && $request->status != null && $request->status == 'inactive') {
            $result->onlyTrashed();
        } else {
            $result->withTrashed();
        }

        $categories = $result->paginate(10);
        return view('admin.category.index', compact('categories'));
    }

    public function add()
    {
        return view('admin.category.add');
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'name' => 'required|max:50|unique:categories,name',
            'slug' => 'required|unique:categories,slug',
            'type' => "nullable",
            'description' => 'max:255',
        ]);

        $check = Category::insert($validate);
        if ($check) {
            return back()->with('msgSuccess', 'Successfully created');
        }
        return back()->with('msgError', '');
    }

    public function edit(Category $category)
    {
        return view('admin.category.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $validate = $request->validate([
            'name' => 'required|max:50|unique:categories,name,' . $id,
            'slug' => 'required|unique:categories,slug,' . $id,
            'type' => "nullable",
            'description' => 'max:255',
        ]);

        $check = Category::where('id', $id)->update($validate);
        if ($check) {
            return back()->with('msgSuccess', 'Update successful');
        }
        return back()->with('msgError', 'Update failed!');
    }

    public function softDelete($id)
    {
        $check = Category::destroy($id);
        if ($check) {
            return back()->with('msgSuccess', 'Change status successful');
        }
        return back()->with('msgError', 'Change status failed!');
    }

    public function restore($id)
    {
        $check = Category::onlyTrashed()->where('id', $id)->restore();
        if ($check) {
            return back()->with('msgSuccess', 'Restore successful');
        }
        return back()->with('msgError', 'Restore failed!');
    }

    public function forceDelete($id)
    {
        $category = Category::onlyTrashed()->where('id', $id)->first();
        if ($category && $category->type == 1) {
            $checkPostExist = Post::where("category_id", $id)->get();
            if ($checkPostExist->count() > 0) {
                return back()->with('msgError', 'There are ' . $checkPostExist->count() . ' products in the post, cannot delete');
            }
        } elseif ($category && $category->type == 2) {
            $checkProjectExist = Project::where("category_project_id", $id)->get();
            if ($checkProjectExist->count() > 0) {
                return back()->with('msgError', 'There are ' . $checkProjectExist->count() . ' products in the project, cannot delete');
            }
        }
        $check = Category::onlyTrashed()->where('id', $id)->forceDelete();
        if ($check) {
            return back()->with('msgSuccess', 'Delete successful');
        }
        return back()->with('msgError', 'Delete failed!');
    }
}
