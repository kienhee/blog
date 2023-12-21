<?php

namespace App\Http\Controllers\Admin\Feedback;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $result = Feedback::query();

        if ($request->has('keywords') && $request->keywords != null) {
            $result->where('name', 'like', '%' . $request->keywords . '%');
        }

        if ($request->has('category_id') && $request->category_id != null) {
            $result->where('category_id', '=',  $request->category_id);
        }

        if ($request->has('sort') && $request->sort != null) {
            $result->orderBy('created_at', $request->sort);
        } else {
            $result->orderBy('created_at', 'desc');
        }

        $feedbacks = $result->paginate(10);
        return view('admin.feedback.index', compact('feedbacks'));
    }

    public function add()
    {
        return view('admin.feedback.add');
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'name' => 'required|string|max:255',
            'career' => 'required|string|max:255',
            'feedback' => 'required|string|max:255',
        ]);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = $file->hashName();
            $path = $file->storePubliclyAs('public/photos/1/feedback', $filename);
            $url = Storage::url($path);
            $validate['avatar'] = $url;
        }

        $check = Feedback::insert($validate);
        if ($check) {
            return back()->with('msgSuccess', 'Successfully created');
        }
        return back()->with('msgError', 'Failed to add feedback!');
    }

    public function edit(Feedback $feedback)
    {
        return view('admin.feedback.edit', compact('feedback'));
    }

    public function update(Request $request, $id)
    {
        $validate = $request->validate([
            'avatar' => 'nullable',
            'name' => 'required|string|max:255',
            'career' => 'required|string|max:255',
            'feedback' => 'required|string|max:255',
        ]);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = $file->hashName();
            $path = $file->storePubliclyAs('public/photos/1/feedback', $filename);
            $url = Storage::url($path);
            $validate['avatar'] = $url;
        }

        $check = Feedback::where('id', $id)->update($validate);
        if ($check) {
            return back()->with('msgSuccess', 'Update successful');
        }
        return back()->with('msgError', 'Update failed!');
    }

    public function delete($id)
    {
        $check =
            Feedback::destroy($id);
        if ($check) {
            return back()->with('msgSuccess', 'Delete successful');
        }
        return back()->with('msgError', 'Delete failed!');
    }
}
