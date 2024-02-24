<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\PostReaction;
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
            'media.*' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:20480', // Adjust as needed
            'media_type' => 'required|in:1,2', // 1 - photo, 2 - video
        ]);

        $user = auth()->user();

        $post = new Post([
            'user_id' => $user->id,
            'caption' => $request->input('caption', ''),
        ]);

        $post->save();
        // Save media files
        foreach ($request->file('media') as $file) {
            $dir = '/uploads/posts/';
            $path = Helper::saveImageToServer($file, $dir);

            // Create post media entry
            $postMedia = new PostMedia([
                'post_id' => $post->id,
                'media_path' => $path,
                'media_type' => $request->input('media_type'),
            ]);
            $postMedia->save();
        }

        return response()->json(['status_code' => 1, 'data' => [], 'message' => 'Post updated']);
    }
    public function fetchPosts()
    {
        $user = auth()->user();

        $posts = Post::with(['user:id,name,profile_picture']) // Adjust the relationship as needed
            ->leftJoin('post_reactions', function ($join) use ($user) {
                $join->on('posts.id', '=', 'post_reactions.post_id')
                    ->where('post_reactions.user_id', '=', $user->id);
            })
            ->leftJoin('post_medias','post_medias.post_id','posts.id')
            ->select('posts.*', 'post_reactions.reaction as reaction','post_medias.media_path','post_medias.media_type')
            ->orderByDesc('posts.created_at')
            ->where('status',1)
            ->paginate(10); // Adjust the number of posts per page as needed
            
            $posts->getCollection()->transform(function ($post) {
                if ($post->media_path) {
                    $post->media_path = config('app.media_base_url') . $post->media_path;
                }
                return $post;
            });
        return response()->json(['status_code' => 1, 'data' => ['posts' => $posts], 'message' => 'Post fetched']);
    }

    public function likePost(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
        ]);
        $user = auth()->user();

        $postReaction = PostReaction::where('user_id', $user->id)
        ->where('post_id', $request->input('post_id'))
        ->first();

        if ($postReaction) {
            if($postReaction->reaction == 'LIKE') {
                $postReaction->delete();
                $message = "Liking removed";
            }
            else {
                $postReaction->update(['reaction' => 'LIKE']);
                $message = "Post liked";
            }
            
            return response()->json(['status_code' => 1, 'data' => [], 'message' => $message]);
        }

        $postReaction = PostReaction::create([
            'user_id' => $user->id,
            'post_id' => $request->input('post_id'),
            'reaction' => 'LIKE',
        ]);

        $this->updatePostReactionCount($request->input('post_id'),'LIKE');
        $message = "Post liked";


        return response()->json(['status_code' => 1, 'data' => [], 'message' => $message]);
    }

    public function disLikePost(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
        ]);
        $user = auth()->user();

        $postReaction = PostReaction::where('user_id', $user->id)
        ->where('post_id', $request->input('post_id'))
        ->first();

        if ($postReaction) {
            if($postReaction->reaction == 'DISLIKE') {
                $postReaction->delete();
                $message = "Disliking removed";
            }
            else {
                $postReaction->update(['reaction' => 'DISLIKE']);
                $message = "Post disliked";
            }
            
            return response()->json(['status_code' => 1, 'data' => [], 'message' => $message]);
        }

        $postReaction = PostReaction::create([
            'user_id' => $user->id,
            'post_id' => $request->input('post_id'),
            'reaction' => 'DISLIKE',
        ]);

        $this->updatePostReactionCount($request->input('post_id'),'DISLIKE');
        $message = "Post disliked";


        return response()->json(['status_code' => 1, 'data' => [], 'message' => $message]);
    }
    private function updatePostReactionCount($post_id,$reaction)
    {
        if($reaction == 'LIKE') {
            $postReactionCount = PostReaction::where('post_id', $post_id)
            ->where('reaction',$reaction)
            ->count();

            $post = Post::where('id', $post_id)
            ->first();
    
            if ($post) {
                $post->update(['like_count' => $postReactionCount]);
            }
        }
        else if($reaction == 'DISLIKE') {
            $postReactionCount = PostReaction::where('post_id', $post_id)
            ->where('reaction',$reaction)
            ->count();

            $post = Post::where('id', $post_id)
            ->first();
    
            if ($post) {
                $post->update(['dislike_count' => $postReactionCount]);
            }
        }
    }
}

?>