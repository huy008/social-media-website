<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Models\Reaction;
use Illuminate\Http\Request;
use App\Models\PostAttachment;
use App\Notifications\PostDeleted;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Auth;
use App\Notifications\CommentDeleted;
use App\Http\Requests\StorePostRequest;
use App\Http\Resources\CommentResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Requests\UpdateCommentRequest;

class PostController extends Controller
{
     public function view(Post $post)
     {
          $post->loadCount('reactions');
          $post->load([
               'comments' => function ($query) {
                    $query->withCount('reactions');
               },
          ]);

          return inertia('Post/View', [
               'post' => new PostResource($post)
          ]);
     }
     /**
      * Store a newly created resource in storage.
      */
     public function store(StorePostRequest $request)
     {
          $data = $request->validated();

          $user = $request->user();

          DB::beginTransaction();
          $allFilePaths = [];
          try {
               $post = Post::create($data);

               /** @var \Illuminate\Http\UploadedFile[] $files */
               $files = $data['attachments'] ?? [];
               foreach ($files as $file) {
                    $path = $file->store('attachments/' . $post->id, 'public');
                    $allFilePaths[] = $path;
                    PostAttachment::create([
                         'post_id' => $post->id,
                         'name' => $file->getClientOriginalName(),
                         'path' => $path,
                         'mime' => $file->getMimeType(),
                         'size' => $file->getSize(),
                         'created_by' => $user->id
                    ]);
               }

               DB::commit();
          } catch (\Exception $e) {
               foreach ($allFilePaths as $path) {
                    Storage::disk('public')->delete($path);
               }
               DB::rollBack();
               throw $e;
          }

          return back();
     }

     /**
      * Update the specified resource in storage.
      */
     public function update(UpdatePostRequest $request, Post $post)
     {
          $user = $request->user();

          DB::beginTransaction();
          $allFilePaths = [];
          try {
               $data = $request->validated();
               $post->update($data);

               $deleted_ids = $data['deleted_file_ids'] ?? [];

               $attachments = PostAttachment::query()
                    ->where('post_id', $post->id)
                    ->whereIn('id', $deleted_ids)
                    ->get();

               foreach ($attachments as $attachment) {
                    $attachment->delete();
               }

               /** @var \Illuminate\Http\UploadedFile[] $files */
               $files = $data['attachments'] ?? [];
               foreach ($files as $file) {
                    $path = $file->store('attachments/' . $post->id, 'public');
                    $allFilePaths[] = $path;
                    PostAttachment::create([
                         'post_id' => $post->id,
                         'name' => $file->getClientOriginalName(),
                         'path' => $path,
                         'mime' => $file->getMimeType(),
                         'size' => $file->getSize(),
                         'created_by' => $user->id
                    ]);
               }

               DB::commit();
          } catch (\Exception $e) {
               foreach ($allFilePaths as $path) {
                    Storage::disk('public')->delete($path);
               }
               DB::rollBack();
               throw $e;
          }

          return back();
     }

     /**
      * Remove the specified resource from storage.
      */
     public function destroy(Post $post)
     {
          $id = Auth::id();

          if ($post->isOwner($id) || $post->group && $post->group->isAdmin($id)) {
               $post->delete();
               return back();
          }

          return response("You don't have permission to delete this post", 403);
     }

     public function downloadAttachment(PostAttachment $attachment)
     {
          return response()->download(Storage::disk('public')->path($attachment->path), $attachment->name);
     }

     public function postReaction(Request $request, Post $post)
     {
          // $data = $request->validate([
          //      'reaction' => [Rule::enum(PostReactionEnum::class)]
          // ]);
          $userId = Auth::id();
          $reaction = Reaction::where('user_id', $userId)
               ->where(
                    'object_id',
                    $post->id
               )
               ->where('object_type', Post::class)
               ->first();
          if ($reaction) {
               $hasReaction = false;
               $reaction->delete();
          } else {
               $hasReaction = true;
               Reaction::create([
                    'object_id' => $post->id,
                    'object_type' => Post::class,
                    'user_id' => $userId,
                    'type' => $request->input('reaction'),
               ]);
          }

          $reactions = Reaction::where('object_id', $post->id)->where('object_type', Post::class)->count();

          return response([
               'num_of_reactions' => $reactions,
               'current_user_has_reaction' => $hasReaction
          ]);
     }

     public function createComment(Request $request, Post $post)
     {
          $data = $request->validate([
               'comment' => ['required'],
               'parent_id' => ['nullable', 'exists:comments,id']
          ]);

          $comment = Comment::create([
               'post_id' => $post->id,
               'comment' => nl2br($data['comment']),
               'user_id' => Auth::id(),
               'parent_id' => $data['parent_id'] ?: null
          ]);

          return response(new CommentResource($comment), 201);
     }


     public function deleteComment(Comment $comment)
     {
          $post = $comment->post;
          $id = Auth::id();
          if ($comment->isOwner($id) || $post->isOwner($id)) {
               $comment->delete();
               return response('', 204);
          }

          return response("You don't have permission to delete this comment.", 403);
     }

     public function updateComment(UpdateCommentRequest $request, Comment $comment)
     {
          $data = $request->validated();

          $comment->update([
               'comment' => nl2br($data['comment'])
          ]);

          return new CommentResource($comment);
     }


     public function commentReaction(Request $request, Comment $comment)
     {
          // $data = $request->validate([
          //      'reaction' => [Rule::enum(ReactionEnum::class)]
          // ]);

          $userId = Auth::id();
          $reaction = Reaction::where('user_id', $userId)
               ->where('object_id', $comment->id)
               ->where('object_type', Comment::class)
               ->first();

          if ($reaction) {
               $hasReaction = false;
               $reaction->delete();
          } else {
               $hasReaction = true;
               Reaction::create([
                    'object_id' => $comment->id,
                    'object_type' => Comment::class,
                    'user_id' => $userId,
                    'type' => $request->input('reaction'),
               ]);
          }

          $reactions = Reaction::where('object_id', $comment->id)->where('object_type', Comment::class)->count();

          return response([
               'num_of_reactions' => $reactions,
               'current_user_has_reaction' => $hasReaction
          ]);
     }
}
