<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function commentOnPost(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'post_id' => 'required|exists:posts,id'
        ]);

        $user = auth()->user();

        $post = Post::findOrFail($request->input('post_id'));

        $comment = new Comment();
        $comment->user_id = $user->id;
        $comment->post_id = $post->id;
        $comment->content = $request->input('content');
        $comment->save();

        return response()->json(['status_code' => 1, 'message' => 'Comment posted successfully']);
    }
    public function replyToComment(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'comment_id' => 'required|exists:comments,id'
        ]);

        $user = auth()->user();

        $comment = Comment::findOrFail($request->input('comment_id'));

        $reply = new CommentReply();
        $reply->user_id = $user->id;
        $reply->comment_id = $comment->id;
        $reply->content = $request->input('content');
        $reply->save();

        return response()->json(['status_code' => 1, 'message' => 'Reply posted successfully']);
    }
    public function fetchCommentsWithReplies(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
        ]);

        $comments = Comment::where('post_id',$request->input('post_id'))
        ->with(['replies.user:id,name,profile_picture', 'user:id,name,profile_picture'])
        ->paginate(10);

        return response()->json(['status_code' => 1, 'data' => ['comments' => $comments], 'message' => 'Comments fetched successfully']);
    }
}
