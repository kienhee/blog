<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Feedback;
use App\Models\Post;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ClientController extends Controller
{
    public function home(Request $request)
    {
        $result = Post::query();
        // dd($request);
        if ($request->has('search') && $request->search != null) {
            $result->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->has('tag') && $request->tag != null) {
            $result->where('tags', 'like', '%' . $request->tag . '%');
        }
        if ($request->has('category') && $request->category != null) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                $result->where('category_id', '=', $category->id);
            } else {
                abort(404);
            }
        }
        if ($request->has('sort') && $request->sort != null) {
            $result->orderBy('created_at', $request->sort);
        } else {
            $result->orderBy('created_at', 'desc');
        }

        $posts = $result->paginate(10);
        return view('client.index', compact('posts'));
    }
    public function author()
    {
        $projects = Project::orderBy('created_at', 'desc')->get();
        $categories = getAllCategories(2);
        $feedbacks = Feedback::orderBy('created_at', 'desc')->get();

        return view('client.author', compact('projects', 'categories', 'feedbacks'));
    }
    public function blog($slug)
    {
        $post = Post::where('slug', $slug)->first();

        if ($post) {
            $comments = Comment::where('post_id', $post->id)->orderBy('created_at', 'desc')->get();
            // Check if the post has been viewed in the current session
            if (!Session::has('viewed_post_' . $post->id)) {
                // If not, increment the view count and mark the post as viewed in the session
                $post->increment('views');
                Session::put('viewed_post_' . $post->id, true);
            }

            return view('client.blog', compact('post', 'comments'));
        } else {
            abort(404);
        }
    }
    public function commentPost(Request $request, $id)
    {
        // Validate form data
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'content' => 'required|string',
        ]);
        $data['comment_id'] = 0;
        $data['post_id'] = $id;
        $check = Comment::insert($data);
        if ($check) {
            return back()->with('msgSuccess', 'Bình luận đã được thêm thành công!');
        }
        return back()->with('msgError', 'Bình luận không công, vui lòng thử lại!');
    }
    public function work($slug)
    {
        $work = Project::where('slug', $slug)->first();
        if ($work) {
            return view('client.work', compact('work'));
        } else {
            abort(404);
        }
    }
}
