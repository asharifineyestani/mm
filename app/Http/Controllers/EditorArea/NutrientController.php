<?php

namespace App\Http\Controllers\EditorArea;

use App\Models\Addable;
use App\Models\Study\Category;
use App\Models\Study\Nutrient;
use App\Http\Requests\NutrientRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class NutrientController extends Controller
{
    public function index()
    {
        return view('editorArea.nutrients.index');
    }

	public function getNutrients(Request $request)
	{
		$query = Nutrient::select(
			'nutrients.id',
			'nutrients.name',
			'nutrients.information',
			'nutrients.general_des'
		);

		$results = datatables($query)
			->addColumn('operation', function ($nutrient){
				return view('editorArea.nutrients.partials._operation', [
					'nutrient_id' => $nutrient->id
				]);
			})
			->editColumn('information', function ($nutrient){
				return $nutrient->information ? '<section class="small">' . $nutrient->information . '</section>' : '-';
			})
			->editColumn('general_des', function ($nutrient){
				return $nutrient->general_des ? '<section class="small">' . $nutrient->general_des . '</section>' : '-';
			})
			->rawColumns(['information', 'general_des', 'operation'])
			->make(true);

		return $results;
    }

    public function create()
    {
        $categories = Category::all();
        return view('editorArea.nutrients.create', compact('categories'));
    }

    public function store(NutrientRequest $request)
    {
        $nutrient = Nutrient::create($request->all());
        if ($nutrient) {
	        /*save images*/
	        $images = (array)$request->file('images');
	        $counter = 0;
	        foreach ($images as $image) {
		        if ($counter < 5){
			        $image_path = $this->storeMedia($image, 'picture');
			        if ($image_path){
				        Addable::create([
					        'addable_id' => $nutrient->id,
					        'addable_type' => 'App\Models\Study\Nutrient',
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
					        'addable_id' => $nutrient->id,
					        'addable_type' => 'App\Models\Study\Nutrient',
					        'media_path' => $video_path,
					        'category' => 'VIDEO',
				        ]);
			        }
		        }
		        $counter++;
	        }
	        /*\ save videos*/


	        Session::flash('alert-info', 'success,' . __('mm.popup.add.success', ['name' => __('study.nutrient.singular')]));
            return redirect()->route('EditorArea.nutrients.index');
        };
        Session::flash('alert-info', 'error,' . __('mm.popup.add.error', ['name' => __('study.nutrient.singular')]));
        return redirect()->back();
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $categories = Category::all();
        $nutrient = Nutrient::find($id);
        return view('editorArea.nutrients.update', compact('nutrient', 'categories'));
    }

    public function update(NutrientRequest $request, $id)
    {
        $nutrient = Nutrient::findOrFail($id);
        $update = $nutrient->update($request->all());
        if ($update) {
	        /*save images*/
	        $images = (array)$request->file('images');
	        $counter = 0;
	        foreach ($images as $image) {
		        if ($counter < 5){
			        $image_path = $this->storeMedia($image, 'picture');
			        if ($image_path){
				        Addable::create([
					        'addable_id' => $nutrient->id,
					        'addable_type' => 'App\Models\Study\Nutrient',
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
					        'addable_id' => $nutrient->id,
					        'addable_type' => 'App\Models\Study\Nutrient',
					        'media_path' => $video_path,
					        'category' => 'VIDEO',
				        ]);
			        }
		        }
		        $counter++;
	        }
	        /*\ save videos*/


	        Session::flash('alert-info', 'success,' . __('mm.popup.update.success', ['name' => __('study.nutrient.singular')]));
            return redirect()->back();
        }
        Session::flash('alert-info', 'error,' . __('mm.popup.update.error', ['name' => __('study.nutrient.singular')]));
        return redirect()->back();
    }

    public function destroy($id)
    {
    	$nutrient = Nutrient::findOrFail($id);
    	$addables = $nutrient->addables;
        if (Nutrient::destroy($id)) {
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
		$addable = Addable::findOrFail($id);
		$media_path = $addable->media_path;

		if ($addable->delete()){
			$this->unlinkMedia($media_path);
			die(true);
		}else{
			die(false);
		}
	}
}
