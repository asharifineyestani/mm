<?php

namespace App\Http\Controllers\EditorArea;

use App\Models\Addable;
use App\Models\Study\Category;
use App\Models\Study\Supplement;
use App\Http\Requests\SupplementRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;


class SupplementController extends Controller
{
    public function index()
    {
        return view('editorArea.supplements.index');
    }

	public function getSupplements(Request $request)
	{
		$query = Supplement::select(
			'supplements.id',
			'supplements.name',
			'supplements.descriptions',
			'supplements.general_des'
		);

		$results = datatables($query)
			->addColumn('operation', function ($supplement){
				return view('editorArea.supplements.partials._operation', [
					'supplement_id' => $supplement->id
				]);
			})
			->editColumn('descriptions', function ($supplement){
				return $supplement->descriptions ? '<section class="small">' . $supplement->descriptions . '</section>' : '-';
			})
			->editColumn('general_des', function ($supplement){
				return $supplement->general_des ? '<section class="small">' . $supplement->general_des . '</section>' : '-';
			})
			->rawColumns(['descriptions', 'general_des', 'operation'])
			->make(true);

		return $results;
    }

    public function create()
    {
        $categories = Category::all();
        return view('editorArea.supplements.create', compact('categories'));
    }

    public function store(SupplementRequest $request)
    {
        $supplement = Supplement::create($request->all());
        if ($supplement) {
	        /*save images*/
	        $images = (array)$request->file('images');
	        $counter = 0;
	        foreach ($images as $image) {
		        if ($counter < 5){
			        $image_path = $this->storeMedia($image, 'picture');
			        if ($image_path){
				        Addable::create([
					        'addable_id' => $supplement->id,
					        'addable_type' => 'App\Models\Study\Supplement',
					        'media_path' => $image_path,
					        'category' => 'PICTURE',
				        ]);
			        }

		        }
		        $counter++;
	        }
	        /*\ save images*/

	        /* save videos */
	        $videos = (array)$request->file('videos');
	        $counter = 0;
	        foreach ($videos as $video){
		        if ($counter < 2){
			        $video_path = $this-> storeMedia($video, 'video');
			        if ($video_path){
				        Addable::create([
					        'addable_id' => $supplement->id,
					        'addable_type' => 'App\Models\Study\Supplement',
					        'media_path' => $video_path,
					        'category' => 'VIDEO',
				        ]);
			        }
		        }
		        $counter++;
	        }
	        /*\ save videos*/


	        Session::flash('alert-info', 'success,' . __('mm.popup.add.success', ['name' => __('study.supplement.singular')]));
            return redirect()->route('EditorArea.supplements.index');
        };
        Session::flash('alert-info', 'error,' . __('mm.popup.add.error', ['name' => __('study.supplement.singular')]));
        return redirect()->back();
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $categories = Category::all();
        $supplement = Supplement::find($id);
        return view('editorArea.supplements.update', compact('supplement', 'categories'));
    }

    public function update(SupplementRequest $request, $id)
    {
        $supplement = Supplement::findOrFail($id);
        $update = $supplement->update($request->all());
        if ($update) {
	        /*save images*/
	        $images = (array)$request->file('images');
	        $counter = 0;
	        foreach ($images as $image) {
		        if ($counter < 5){
			        $image_path = $this->storeMedia($image, 'picture');
			        if ($image_path){
				        Addable::create([
					        'addable_id' => $supplement->id,
					        'addable_type' => 'App\Models\Study\Supplement',
					        'media_path' => $image_path,
					        'category' => 'PICTURE',
				        ]);
			        }

		        }
		        $counter++;
	        }
	        /*\ save images*/


	        /* save videos */
	        $videos = (array)$request->file('videos');
	        $counter = 0;
	        foreach ($videos as $video){
		        if ($counter < 2){
			        $video_path = $this-> storeMedia($video, 'video');
			        if ($video_path){
				        Addable::create([
					        'addable_id' => $supplement->id,
					        'addable_type' => 'App\Models\Study\Supplement',
					        'media_path' => $video_path,
					        'category' => 'VIDEO',
				        ]);
			        }
		        }
		        $counter++;
	        }
	        /*\ save videos*/

            Session::flash('alert-info', 'success,' . __('mm.popup.update.success', ['name' => __('study.supplement.singular')]));
            return redirect()->back();
        }
        Session::flash('alert-info', 'error,' . __('mm.popup.update.error', ['name' => __('study.supplement.singular')]));
        return redirect()->back();
    }

    public function destroy($id)
    {
	    $supplement = Supplement::findOrFail($id);
	    $addables = $supplement->addables;
        if ($supplement->delete()) {
	        if ($addables){
		        foreach ($addables as $addable){
			        $this->unlinkMedia($addable->media_path);
			        $addable->delete();
		        }
	        }
            die(true);
        }
        die(false);
    }

	public function deleteAddable($id)
	{
		$addable = Addable::find($id);
		$media_path = $addable->media_path;

		if ($addable->delete()){
			$this->unlinkMedia($media_path);
			die(true);
		}else{
			die(false);
		}
	}
}
