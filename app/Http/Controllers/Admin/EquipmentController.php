<?php

namespace App\Http\Controllers\Admin;

use App\Models\Addable;
use App\Models\Study\Category;
use App\Models\Study\Equipment;
use App\Http\Requests\EquipmentRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class EquipmentController extends Controller
{
    public function index()
    {
        return view('editorArea.equipments.index');
    }

	public function getEquipments(Request $request)
	{
		$query = Equipment::select(
			'equipment.id',
			'equipment.name',
			'equipment.information',
			'equipment.workouts'
		);

		$results = datatables($query)
			->addColumn('operation', function ($equipment){
				return view('editorArea.equipments.partials._operation', [
					'equipment_id' => $equipment->id
				]);
			})
			->editColumn('information', function ($equipment){
				return $equipment->information ? '<span class="small">' . $equipment->information . '</span>' : '-';
			})
			->editColumn('workouts', function ($equipment){
				return $equipment->workouts ? '<span class="small">' . $equipment->workouts . '</span>' : '-';
			})
			->rawColumns(['information', 'workouts', 'operation'])
			->make(true);

		return $results;
    }

    public function create()
    {
        $categories = Category::all();
        return view('editorArea.equipments.create', compact('categories'));
    }

    public function store(EquipmentRequest $request)
    {
        $equipment = Equipment::create($request->all());
        if ($equipment) {
	        /*save images*/
	        $images = (array)$request->file('images');
	        $counter = 0;
	        foreach ($images as $image) {
		        if ($counter < 5){
			        $image_path = $this->storeMedia($image, 'picture');
			        if ($image_path){
				        Addable::create([
					        'addable_id' => $equipment->id,
					        'addable_type' => 'App\Models\Study\Equipment',
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
					        'addable_id' => $equipment->id,
					        'addable_type' => 'App\Models\Study\Equipment',
					        'media_path' => $video_path,
					        'category' => 'VIDEO',
				        ]);
			        }
		        }
		        $counter++;
	        }
	        /*\ save videos*/

            Session::flash('alert-info', 'success,' . __('mm.popup.add.success', ['name' => __('study.equipment.singular')]));
            return redirect()->route('admin.equipments.index');
        };
        Session::flash('alert-info', 'error,' . __('mm.popup.add.error', ['name' => __('study.equipment.singular')]));
        return redirect()->back();
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $categories = Category::all();
        $equipment = Equipment::find($id);
        return view('editorArea.equipments.edit', compact('equipment', 'categories'));
    }

    public function update(EquipmentRequest $request, $id)
    {
        $equipment = Equipment::findOrFail($id);
        $update = $equipment->update($request->all());
        if ($update) {
	        /*save images*/
	        $images = (array)$request->file('images');
	        $counter = 0;
	        foreach ($images as $image) {
		        if ($counter < 5){
			        $image_path = $this->storeMedia($image, 'picture');
			        if ($image_path){
				        Addable::create([
					        'addable_id' => $equipment->id,
					        'addable_type' => 'App\Models\Study\Equipment',
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
					        'addable_id' => $equipment->id,
					        'addable_type' => 'App\Models\Study\Equipment',
					        'media_path' => $video_path,
					        'category' => 'VIDEO',
				        ]);
			        }
		        }
		        $counter++;
	        }
	        /*\ save videos*/


	        Session::flash('alert-info', 'success,' . __('mm.popup.update.success', ['name' => __('study.equipment.singular')]));
            return redirect()->back();
        }
        Session::flash('alert-info', 'error,' . __('mm.popup.update.error', ['name' => __('study.equipment.singular')]));
        return redirect()->back();
    }

    public function destroy($id)
    {
    	$equipment = Equipment::findOrFail($id);
    	$addables = $equipment->addables;
        if (Equipment::destroy($id)) {
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

    function deleteAddable($id){
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
