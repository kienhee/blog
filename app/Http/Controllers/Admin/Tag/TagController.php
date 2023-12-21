<?php

namespace App\Http\Controllers\Admin\Tag;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
  public function index()
  {
    $tags = Tag::all();
    return view('admin.tag.index', compact('tags'));
  }

  public function add()
  {
    return view('admin.tag.add');
  }

  public function store(Request $request)
  {
    $validate = $request->validate([
      'name' => 'required|max:50|unique:tags,name',
    ]);

    $check = Tag::insert($validate);

    if ($check) {
      return back()->with('msgSuccess', 'Added successfully');
    }

    return back()->with('msgError', 'Failed to add!');
  }

  public function edit(Tag $tag)
  {
    return view('admin.tag.edit', compact('tag'));
  }

  public function update(Request $request, $id)
  {
    $validate = $request->validate([
      'name' => 'required|max:50|unique:tags,name,' . $id,
    ]);

    $check = Tag::where('id', $id)->update($validate);

    if ($check) {
      return back()->with('msgSuccess', 'Updated successfully');
    }

    return back()->with('msgError', 'Failed to update!');
  }

  public function delete($id)
  {
    $check = Tag::destroy($id);

    if ($check) {
      return back()->with('msgSuccess', 'Deleted successfully');
    }

    return back()->with('msgError', 'Failed to delete!');
  }
}
