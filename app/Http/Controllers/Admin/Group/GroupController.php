<?php

namespace App\Http\Controllers\Admin\Group;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::all();
        return view('admin.group.index', compact('groups'));
    }

    public function add()
    {
        return view('admin.group.add');
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'name' => 'required|max:50',
        ], [
            "name.max" => "Maximum :max characters",
            "name.required" => "Please enter the group name",
        ]);

        $check = Group::insert($validate);
        if ($check) {
            return back()->with('msgSuccess', 'Group added successfully');
        }
        return back()->with('msgError', 'Failed to add group!');
    }

    public function edit(Group $group)
    {
        return view('admin.group.edit', compact('group'));
    }

    public function update(Request $request, $id)
    {
        $validate = $request->validate([
            'name' => 'required|max:50',
        ], [
            "name.max" => "Maximum :max characters",
            "name.required" => "Please enter the group name",
        ]);

        $check = Group::where('id', $id)->update($validate);
        if ($check) {
            return back()->with('msgSuccess', 'Update successful');
        }
        return back()->with('msgError', 'Update failed!');
    }

    public function delete($id)
    {
        $checkUserExists = User::where('group_id', $id)->get();
        if ($checkUserExists->count() > 0) {
            return back()->with('msgError', 'There are ' . $checkUserExists->count() . ' users in the group, cannot delete');
        }

        $check = Group::destroy($id);
        if ($check) {
            return back()->with('msgSuccess', 'Delete successful');
        }
        return back()->with('msgError', 'Delete failed!');
    }
}
