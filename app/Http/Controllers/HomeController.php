<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Inertia\Inertia;
use App\Models\Group;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\GroupResource;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::query()->latest()->paginate(20);
        $userId = Auth::id();
        $posts = Post::postsForTimeline($userId)
            ->paginate(2);

        $groups = Group::query()
            ->with('currentUserGroup')
            ->select(['groups.*'])
            ->join('group_users AS gu', 'gu.group_id', 'groups.id')
            ->where('gu.user_id', Auth::id())
            ->orderBy('gu.role')
            ->orderBy('name', 'desc')
            ->get();

        $posts = PostResource::collection($posts);
        if ($request->wantsJson()) {
            return $posts;
        }
        return Inertia::render('Home', [
            'posts' => $posts,
            'groups' => GroupResource::collection($groups)
        ]);
    }
}
