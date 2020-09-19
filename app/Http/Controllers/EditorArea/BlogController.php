<?php

namespace App\Http\Controllers\EditorArea;

use App\Models\Addable;
use App\Models\Blog;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class BlogController extends Controller
{
    public $originalPath = '';
    protected function rules($id = '')
	{
		return [
			'title' => 'required',
			'body' => "required",
        ];
	}

    public function index()
    {
        return view('editorArea.blogs.index');
    }

	public function getBlogs(Request $request)
	{
        $query = Blog::select('*');

		$results = datatables($query)
			->addColumn('operation', function ($blog){
				return view('editorArea.blogs.partials._operation', [
					'blog_id' => $blog->id
				]);
			})
			->filterColumn('title', function ($query, $keyword){
			    $sql = 'posts.title like ?';
			    $query->whereRaw($sql, '%' . $keyword . '%');
			})
			->filterColumn('slug', function ($query, $keyword){
                $sql = 'posts.slug like ?';
                $query->whereRaw($sql, '%' . $keyword . '%');
			})
			->rawColumns(['operation'])
			->make(true);

		return $results;
    }

    public function create()
    {
        return view('editorArea.blogs.create');
    }

    public function store(Request $request)
    {
        $data=$request->all();
        $this->pathMedia='/uploads/posts/';
        if ($request->hasFile('avatar')){
             $files = $request->file('avatar');
            if (isset($files[0])){
                $data['media_path'] = $this->storeMedia($files[0], 'picture');
             }
        }
        $data['user_id']=Auth::user()->id;
        $data['slug']=!empty($request['slug']) ? $request['slug'] :str_replace(' ', '-',  $request['title']);
        $request->validate($this->rules());
        $blog = Blog::create($data);

	   if ($blog){
		   Session::flash('alert-info', 'success,'.__('mm.popup.add.success',['name'=>__('mm.blog')]));
	   }

	   return redirect()->route('EditorArea.blogs.index');;
    }

    public function edit($id)
    {
    	$blog = Blog::where('id', $id)
		    ->firstOrFail();
        return view('editorArea.blogs.edit', [
        	'blog' => $blog,
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $request->validate($this->rules());
        $blog = Blog::find($id);
        // save avatar
        $old_image_path = $blog->media_path;
        $this->pathMedia='/uploads/posts/';
        if ($request->hasFile('avatar')){
            $files = $request->file('avatar');
            if (isset($files[0])){
                $media_path = $this->storeMedia($files[0], 'picture');
                $blog->media_path= $media_path;
                $this->unlinkMedia($old_image_path);
            }
        }
        $data['user_id']=Auth::user()->id;
        $data['slug']=!empty($request['slug']) ? $request['slug'] :str_replace(' ', '-',  $request['title']);
        $blog->update($data);
        Session::flash('alert-info', 'success,'.__('mm.popup.update.success',['name'=>__('mm.blog')]));
        return redirect('admin/editor-area/blogs');
    }

    public function destroy($id)
    {
        $record = Blog::find($id);
        if (Blog::where('id', $id)->delete()){
            $this->unlinkMedia($record->media_path);
            die(true);
        }
        die(false);
    }
}
