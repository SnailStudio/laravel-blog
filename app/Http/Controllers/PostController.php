<?php

namespace App\Http\Controllers;

use App\Http\Repositories\CategoryRepository;
use App\Http\Repositories\CommentRepository;
use App\Http\Repositories\MapRepository;
use App\Http\Repositories\PostRepository;
use App\Http\Repositories\TagRepository;
use App\Http\Requests;
use App\Mail\WelcomeToLufficc;
use App\Notifications\UserRegistered;
use App\Post;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Purifier;

class PostController extends Controller
{
    protected $postRepository;
    protected $tagRepository;
    protected $categoryRepository;
    protected $mapRepository;
    protected $commentRepository;

    /**
     * PostController constructor.
     * @param PostRepository $postRepository
     * @param CategoryRepository $categoryRepository
     * @param TagRepository $tagRepository
     * @param MapRepository $mapRepository
     * @param CommentRepository $commentRepository
     */
    public function __construct(PostRepository $postRepository,
                                CategoryRepository $categoryRepository,
                                TagRepository $tagRepository,
                                MapRepository $mapRepository,
                                CommentRepository $commentRepository)
    {
        $this->postRepository = $postRepository;
        $this->categoryRepository = $categoryRepository;
        $this->tagRepository = $tagRepository;
        $this->mapRepository = $mapRepository;
        $this->commentRepository = $commentRepository;

        $this->middleware(['auth', 'admin'], ['except' => ['show', 'index']]);
    }


    public function index()
    {
        $page_size = 7;
        if ($map = $this->mapRepository->get('page_size')) {
            $page_size = $map->value;
        }
        $posts = $this->postRepository->pagedPosts($page_size);
        return view('post.index', compact('posts'));
    }

    public function create()
    {
        return view('post.create',
            [
                'categories' => $this->categoryRepository->getAll(),
                'tags' => $this->tagRepository->getAll(),
            ]
        );
    }

    public function store(Request $request)
    {
        $this->validatePostForm($request);

        if ($this->postRepository->create($request))
            return redirect('admin/posts')->with('success', '文章' . $request['name'] . '创建成功');
        else
            return redirect('admin/posts')->withErrors('文章' . $request['name'] . '创建失败');
    }


    public function show($slug)
    {
        $post = $this->postRepository->get($slug);
        if (!isAdmin(auth()->user())) {
            $post->increment('view_count');
        }
        return view('post.show', compact('post'));
    }

    public function preview($slug)
    {
        $post = Post::withoutGlobalScopes()->where('slug', $slug)->with('tags')->first();
        if (!$post)
            abort(404);
        $preview = true;
        return view('post.show', compact('post', 'preview'));
    }

    public function publish($id)
    {
        $post = Post::withoutGlobalScopes()->find($id);
        if ($post->trashed()) {
            return back()->withErrors($post->title . '发布失败，请先恢复删除');
        }
        $this->clearAllCache();
        if ($post->status == 0) {
            $post->status = 1;
            $post->published_at = Carbon::now();
            if ($post->save())
                return back()->with('success', $post->title . '发布成功');
        } else if ($post->status == 1) {
            $post->status = 0;
            if ($post->save())
                return back()->with('success', $post->title . '撤销发布成功');
        }
        return back()->withErrors($post->title . '操作失败');
    }


    public function edit($id)
    {
        $post = Post::withoutGlobalScopes()->find($id);

        $this->checkPolicy('update', $post);

        return view('post.edit', [
            'post' => $post,
            'categories' => $this->categoryRepository->getAll(),
            'tags' => $this->tagRepository->getAll(),
        ]);
    }


    public function update(Request $request, $id)
    {
        $post = Post::withoutGlobalScopes()->find($id);
        $this->checkPolicy('update', $post);
        $this->validatePostForm($request, true);

        if ($this->postRepository->update($request, $post)) {
            return redirect('admin/posts')->with('success', '文章' . $request['name'] . '修改成功');
        } else
            return redirect('admin/posts')->withErrors('文章' . $request['name'] . '修改失败');
    }

    public function restore($id)
    {
        $post = Post::withoutGlobalScopes()->findOrFail($id);
        if ($post->trashed()) {
            $post->restore();
            $this->clearAllCache();
            return redirect()->route('admin.posts')->with('success', '恢复成功');
        }
        return redirect()->route('admin.posts')->withErrors('恢复失败');
    }


    public function destroy($id)
    {

        $post = Post::withoutGlobalScopes()->findOrFail($id);
        $redirect = route('admin.posts');
        if (request()->has('redirect'))
            $redirect = request()->input('redirect');

        if ($post->trashed()) {
            $result = $post->forceDelete();
        } else {
            $result = $post->delete();
        }
        if ($result) {
            $this->clearAllCache();
            return redirect($redirect)->with('success', '删除成功');
        } else
            return redirect($redirect)->withErrors('删除失败');
    }

    private function validatePostForm(Request $request, $update = false)
    {
        $v = [
            'title' => 'required',
            'description' => 'required',
            'category_id' => 'required',
            'content' => 'required',
        ];
        if (!$update)
            $v = array_merge($v, ['slug' => 'required|unique:posts']);
        $this->validate($request, $v);
    }

    public function clearAllCache()
    {
        cache()->flush();
    }
}
