<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\PostForm;
use App\Files\Uploaders\ImageUpload;
use App\Blog\{Topic, Post};

class BlogController extends Controller
{
    public function create()
    {
        $topics = Topic::all();

        return view('admin.pages.blog.post.create', compact('topics'));
    }

    public function store(Request $request, PostForm $form)
    {
        $post = Post::create([
            'slug' => str_slug($form->title),
            'creator_id' => auth()->guard('admin')->user()->id,
            'title' => $form->title,
            'description' => $form->description,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for(Post::class)
                                                       ->name(str_slug($form->title))
                                                       ->withThumbnail()
                                                       ->cropped()
                                                       ->upload(),
            'content' => $form->content,
            'references' => $request->references ? serialize($request->references) : null,
            'gift_path' => $form->gift_path,
            'cover_credits' => $form->cover_credits,
            'reading_time' => calculateReadingTime($form->content)
        ]);

        $post->topics()->attach($request->topics);

        return redirect(route('admin.posts.index'))->with('status', 'The post has been successfuly created!');
    }

    public function edit(Post $post)
    {
        $topics = Topic::all();

        return view('admin.pages.blog.post.edit', compact(['post', 'topics']));
    }

    public function update(Request $request, Post $post, PostForm $form)
    {
        $post->update([
            'slug' => str_slug($form->title),
            'title' => $form->title,
            'description' => $form->description,
            'content' => $form->content,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for($post)
                                                       ->name(str_slug($form->title))
                                                       ->withThumbnail()
                                                       ->cropped()
                                                       ->upload(),
            'references' => $request->references ? serialize($request->references) : null,
            'gift_path' => $form->gift_path,
            'reading_time' => calculateReadingTime($form->content),
            'cover_credits' => $form->cover_credits
        ]);

        $post->topics()->sync($request->topics);

        return redirect(route('admin.posts.edit', $post))->with('status', 'The post has been successfuly updated!');
    }

    public function updateStatus(Post $post)
    {
        return $this->updateStatusFor($post);
    }

    public function uploadImage(Request $request)
    {
        try {
            $path = \Storage::disk('public')->putFile(
                    "/blog/content_images", 
                    $request->file('file'));
        } catch (\Exception $e) {
            return response()->json('Sorry, something went wrong...', 404);
        }

        return response()->json(['location' => asset('storage/' . $path)]);
    }
    
    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->back()->with('status', 'The post has been successfuly deleted!');
    }
}
