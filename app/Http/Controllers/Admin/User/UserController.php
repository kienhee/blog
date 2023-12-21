<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $result = User::query();

        if ($request->has('keywords') && $request->keywords != null) {
            $result->where('full_name', 'like', '%' . $request->keywords . '%')
                ->orWhere('email', 'like', '%' . $request->keywords . '%')
                ->orWhere('phone_number', 'like', '%' . $request->keywords . '%');
        }
        if ($request->has('group_id') && $request->group_id != null) {
            $result->where('group_id', $request->group_id);
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
        $users = $result->paginate(10);
        return view('admin.user.index', compact('users'));
    }

    public function add()
    {
        return view('admin.user.add');
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'full_name' => 'required|max:50',
            'group_id' => 'required|numeric',
            'phone_number' => 'required|numeric',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6'
        ]);

        $validate['password'] = Hash::make($validate['password']);

        unset($validate['password_confirmation']);
        $check = User::insert($validate);
        if ($check) {
            return back()->with('msgSuccess', 'Successfully added member');
        }
        return back()->with('msgError', 'Failed to add member!');
    }

    public function edit(User $user)
    {
        return view('admin.user.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->id != $id) {
            return abort(401);
        }

        $validate = $request->validate([
            'full_name' => 'required|max:50',
            'group_id' => 'required|numeric',
            'phone' => 'required|numeric',
            'avatar' =>  'image',
            'career' => "nullable",
            'facebook' => "nullable|url",
            'instagram' => "nullable|url",
            'linkedin' => "nullable|url",
        ]);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = $file->hashName();
            $path = $file->storePubliclyAs('public/photos/1/user', $filename);
            $url = Storage::url($path);
            $validate['avatar'] = $url;
        }

        $check = User::where('id', $id)->update($validate);
        if ($check) {
            return back()->with('msgSuccess', 'Update successful');
        }
        return back()->with('msgError', 'Update failed!');
    }

    public function softDelete($id)
    {
        $check = User::destroy($id);
        if ($check) {
            return back()->with('msgSuccess', 'Change status successful');
        }
        return back()->with('msgError', 'Change status failed!');
    }

    public function restore($id)
    {
        $check = User::onlyTrashed()->where('id', $id)->restore();
        if ($check) {
            return back()->with('msgSuccess', 'Restore successful');
        }
        return back()->with('msgError', 'Restore failed!');
    }

    public function forceDelete($id)
    {
        if (Auth::user()->id != $id) {
            return abort(401);
        }

        $check = User::onlyTrashed()->where('id', $id)->forceDelete();
        if ($check) {
            return back()->with('msgSuccess', 'Delete user successful');
        }
        return back()->with('msgError', 'Delete user failed!');
    }

    public function accountSetting()
    {
        return view('admin.user.Account');
    }
    public function changePw()
    {
        return view('admin.user.change-pw');
    }
    public function handleChangePassword(Request $request, $email)
    {
        if (Auth::user()->email != $email) {
            return abort(401);
        }
        $request->validate([
            'currentPassword' => 'required',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6'
        ]);

        $user = User::where('email', $email)->first();

        if (Hash::check($request->currentPassword, $user->password)) {
            $check = User::where('email', $email)->update(['password' => Hash::make($request->password)]);

            if ($check) {
                return back()->with('msgSuccess', 'Changed password successfully');
            }
            return back()->with('msgError', 'Password change failed!');
        } else {
            return back()->with('msgError', 'Current password is incorrect!');
        }
    }
}
