<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Reaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'caption' => 'nullable|string',
            'media' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:20480', // Adjust as needed
            'media_type' => 'required|in:1,2', // 1 - photo, 2 - video
        ]);


        $user = auth()->user();

        $file = $request->file('media');
        $dir = '/uploads/posts/';
        $path = Helper::saveImageToServer($file,$dir);

        $post = new Post([
            'user_id' => $user->id,
            'caption' => $request->input('caption',''),
            'media_type' => $request->input('media_type'),
            'media_path' => $path
        ]);

        $post->save();

        return response()->json(['status_code' => 1, 'data' => [], 'message' => 'Post updated']);
    }
    public function fetchPosts()
    {
        $posts = Post::with(['user:id,name,profile_picture']) // Adjust the relationship as needed
            ->orderByDesc('created_at')
            ->where('status',1)
            ->paginate(10); // Adjust the number of posts per page as needed

        return response()->json(['status_code' => 1, 'data' => ['posts' => $posts], 'message' => 'Post fetched']);
    }

    public function likePost(Request $request, $postId)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
        ]);
        $user = auth()->user();

        $like = Reaction::firstOrNew([
            'user_id' => $user->id,
            'post_id' => $postId,
        ]);

        $like->reaction = 'like';
        $like->save();

        return response()->json(['status_code' => 1, 'data' => ['like' => $like], 'message' => 'Post liked successfully.']);
    }
}

?>