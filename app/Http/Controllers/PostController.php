<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        If (!Auth::check()){
            return redirect()->route('login');
        }

        $query = Post::query();

        // 絞り込み検索
        
            $searchType = $request->input('search_type');
            $searchKeyword = $request->input('search');

            switch($searchType){
                case 'share_everyone':
                    $query->where('share','1')->where('title', 'like', '%' . $searchKeyword . '%');
                    break;
                case 'all_mine':
                    $query->where('user_id', Auth::id())->where('title', 'like', '%' . $searchKeyword . '%');
                    break;
                case 'share_mine':
                    $query->where('share','1')->where('user_id', Auth::id())->where('title', 'like', '%' . $searchKeyword . '%');
                    break;
                case 'secret_mine':
                    $query->where('share','0')->where('user_id', Auth::id())->where('title', 'like', '%' . $searchKeyword . '%');
                    break;
                default:
                    $query->where('user_id', Auth::id())->where('title', 'like', '%' . $searchKeyword . '%');
                    break;
            }

        
        // 並び替え処理
        $sortType = $request->input('sort', 'newest');

        switch($sortType){

                case 'created_oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'created_newest':
                    $query->orderBy('created_at', 'desc');
                    break;    
                case 'title_asc':
                    $query->orderBy('title', 'asc');
                    break;
                case 'title_desc':
                    $query->orderBy('title', 'desc');
                    break;
                case 'updated_oldest':
                    $query->orderBy('updated_at', 'asc');
                    break;    
                case 'updated_newest':
                    $query->orderBy('updated_at', 'desc');
                    break;
                default:
                    $query->orderBy('updated_at', 'desc');
                    break;
    
        }

        $posts = $query->paginate(10)->withQueryString();
        return view('posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        If (!Auth::check()){
            return redirect()->route('login');
        }
        return view('posts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        If (!Auth::check()){
            return redirect()->route('login');
        }
        
        $request->validate([
            'title' => 'required|max:255',
            'content_failure' => 'required',
            'content_success' => 'required',
            'share' => 'required'
        ]);
        $post = Post::create([
            'title' => $request->title,
            'content_failure' => $request->content_failure,
            'content_success' => $request->content_success,
            'user_id'=> auth()->id(),
            'share' => $request->share
        ]);

        return redirect()->route('posts.show',['post' => $post->id])->with('success','登録できました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $post = Post::with('comments.user')->findOrFail($id);
        $comments = $post->comments()->orderBy('created_at', 'desc')->with('user')->paginate(2);
        return view('posts.show',compact('post','comments'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $post = Post::findOrFail($id);

        If (Auth::id() !== $post->user_id){
            return redirect()->route('posts.index');
        }

        return view('posts.edit',compact('post'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required|max:255',
            'content_failure' => 'required',
            'content_success' => 'required',
            'share' => 'required'
        ]);
        $post =Post::findOrFail($id);

        If (Auth::id() !== $post->user_id){
            return redirect()->route('posts.index');
        }

        $post->update([
            'title' => $request->title,
            'content_failure' => $request->content_failure,
            'content_success' => $request->content_success,
            'share' => $request->share
        ]);
        return redirect()->route('posts.show',['post' => $post->id])->with('success','登録できました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $post = Post::findOrFail($id);

        If (Auth::id() !== $post->user_id){
            return redirect()->route('posts.index');
        }

        $post->delete();
        return redirect()->route('posts.index');
    }
}
