<?php

namespace App\Http\Controllers\Admin;

use App\Models\Addable;
use App\Helpers\Sh4Helper;
use App\Models\Study\Category;
use App\Models\Study\Workout;
use App\Http\Requests\WorkoutRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class WorkoutController extends Controller
{
   public function index()
    {

    	$categories = Category::orderBy('name')->get();

        return view('editorArea.workouts.index', [
        	'categories' => $categories
        ]);
    }

	public function getWorkouts(Request $request)
	{
		$query = Workout::leftJoin('categories', 'workouts.category_id', '=', 'categories.id')
			->with('addables')
			->select(
				'workouts.id as id',
				'workouts.name as name',
				'categories.name as category_name',
				'workouts.main_equipment as main_equipment'
			);

		$results = datatables($query)
			->addColumn('operation', function ($workout){
				return view('editorArea.workouts.partials._operation', [
					'workout_id' => $workout->id
				]);
			})
			->editColumn('category_name', function ($workout){
				return $workout->category_name ? $workout->category_name : '-';
			})
			->addColumn('gif', function ($workout){
				$gif = $workout->addables()->where('category', 'GIF')->first();
				if (!is_null($gif)){
					return '<img width="100" src="' . config('app.url') .  $gif->media_path . '">';
				}else{
					return '-';
				}
			})
			->editColumn('main_equipment', function ($workout){
				return $workout->main_equipment ? $workout->main_equipment : '-';
			})
			->rawColumns(['main_equipment', 'operation', 'gif'])
			->make(true);

		return $results;
    }

    public function create()
    {
        $categories = Category::all();
        return view('editorArea.workouts.create', compact('categories'));
    }

    public function store(WorkoutRequest $request)
    {
        $workout = Workout::create($request->all());
        if ($workout) {
            /*save images*/
	        $images = (array)$request->file('images');
            $counter = 0;
            foreach ($images as $image) {
                if ($counter < 5){
	                $image_path = $this->storeMedia($image, 'picture');
	                if ($image_path){
		                Addable::create([
			                'addable_id' => $workout->id,
			                'addable_type' => 'App\Models\Study\Workout',
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
				          'addable_id' => $workout->id,
				          'addable_type' => 'App\Models\Study\Workout',
				          'media_path' => $video_path,
				          'category' => 'VIDEO',
			          ]);
		       	  }
		       }
		       $counter++;
	        }
	        /*\ save videos*/

	        /* save gif */
	        $gif = $request->file('gif');
	        $path = $this->storeMedia($gif, 'video');
	        if ($path){
		        Addable::create([
			        'addable_id' => $workout->id,
			        'addable_type' => 'App\Models\Study\Workout',
			        'media_path' => $path,
			        'category' => 'GIF',
		        ]);
	        }

            Session::flash('alert-info', 'success,' . __('mm.popup.add.success', ['name' => __('study.workout.singular')]));
            return redirect()->route('admin.workouts.index');
        };
        Session::flash('alert-info', 'error,' . __('mm.popup.add.error', ['name' => __('study.workout.singular')]));
        return redirect()->back();
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $categories = Category::all();
        $workout = Workout::findOrFail($id);
        return view('editorArea.workouts.edit', compact('workout', 'categories'));
    }

    public function update(WorkoutRequest $request, $id)
    {
        $workout = Workout::findOrFail($id);
        $update = $workout->update($request->all());

        if ($update) {
	        /*save images*/
	        $images = (array)$request->file('images');
	        $counter = 0;
	        foreach ($images as $image) {
		        if ($counter < 5){
			        $image_path = $this->storeMedia($image, 'picture');
			        if ($image_path){
				        Addable::create([
					        'addable_id' => $workout->id,
					        'addable_type' => 'App\Models\Study\Workout',
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
					        'addable_id' => $workout->id,
					        'addable_type' => 'App\Models\Study\Workout',
					        'media_path' => $video_path,
					        'category' => 'VIDEO',
				        ]);
			        }
		        }
		        $counter++;
	        }
	        /*\ save videos*/

	        /* save gif */
	        $gif = $request->file('gif');
	        $path = $this->storeMedia($gif, 'video');
	        if ($path){
		        Addable::create([
			        'addable_id' => $workout->id,
			        'addable_type' => 'App\Models\Study\Workout',
			        'media_path' => $path,
			        'category' => 'GIF',
		        ]);
	        }

            Session::flash('alert-info', 'success,' . __('mm.popup.update.success', ['name' => __('study.workout.singular')]));
            //return redirect()->route('admin.workouts.index');
	        return redirect()->back();
        }
        Session::flash('alert-info', 'error,' . __('mm.popup.update.error', ['name' => __('study.workout.singular')]));
        return redirect()->back();
    }

    public function destroy($id)
    {
    	$workout = Workout::findOrFail($id);
    	$addables = $workout->addables;
        if ($workout->delete()) {
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
