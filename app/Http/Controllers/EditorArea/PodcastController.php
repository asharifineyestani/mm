<?php

namespace App\Http\Controllers\EditorArea;

use App\Models\Podcast;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class PodcastController extends Controller
{
    public $prefix = 'podcast';
    protected function rules($id = '')
    {
        return [
            'title' => 'required',
            'link' => "required"
        ];
    }

    public function index()
    {
        $query = Podcast::get();
        return view('editorArea.podcasts.index');
    }

    public function getPodcasts(Request $request)	{
        $query = Podcast::select('*');
        $results = datatables($query)
            ->addColumn('operation', function ($podcast){
                return view('editorArea.podcasts.partials._operation', [
                    'podcast_id' => $podcast->id
                ]);
            })
            ->filterColumn('title', function ($query, $keyword){
                $sql = 'podcasts.title like ?';
                $query->whereRaw($sql, '%' . $keyword . '%');
            })
            ->filterColumn('link', function ($query, $keyword){
                $sql = 'podcasts.link like ?';
                $query->whereRaw($sql, '%' . $keyword . '%');
            })
            ->rawColumns(['operation'])
            ->make(true);

        return $results;
    }

    public function create()
    {
        return view('editorArea.podcasts.create');
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());
        $data=$request->all();
        // save avatar
        if ($request->hasFile('avatar_path')){
            $files = $request->file('avatar_path');
            if (isset($files[0])){
                $image_path = $this->storeMedia($files[0], 'picture');
                $data['avatar_path'] = url('/').$image_path;

            }
        }
        $podcast = Podcast::create($data);

        if ($podcast){
            Session::flash('alert-info', 'success,'.__('mm.popup.add.success',['name'=>__('mm.podcast')]));
        }

        return redirect()->route('EditorArea.podcasts.index');;
    }

    public function edit($id)
    {
        $podcast = Podcast::where('id', $id)
            ->firstOrFail();
        return view('editorArea.podcasts.edit', [
            'podcast' => $podcast,
        ]);
    }

    public function update(Request $request, $id)
    {
        $podcast = Podcast::find($id);
        // save avatar
        $old_image_path = $podcast->avatar_path;
        if ($request->hasFile('avatar_path')){
            $files = $request->file('avatar_path');
            if (isset($files[0])){
                $image_path = $this->storeMedia($files[0], 'picture');
                $podcast->avatar_path= url('/').$image_path;
                $podcast->save();
                // delete old avatar
                $this->unlinkMedia($old_image_path);
            }
        }
        $request->validate($this->rules());
        Podcast::where('id', $id)
            ->update(['title' => $request->title,
                'link'=>$request->link,
                'body'=> !empty($request->body ?$request->body: NULL)
            ]);

        Session::flash('alert-info', 'success,'.__('mm.popup.update.success',['name'=>__('mm.podcast')]));

        return redirect('admin/editor-area/podcasts');
    }

    public function destroy($id)
    {
        if (Podcast::where('id', $id)->delete()){
            die(true);
        }

        die(false);
    }
}
