<?php

namespace App\Http\Controllers\Admin;

use App\Models\Addable;
use App\Models\Study\Category;
use App\Models\Study\Gym;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class GymController extends Controller
{
    private function validation(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'len' => 'numeric',
            'lat' => 'numeric',
        ]);
    }

    public function index()
    {
        return view('editorArea.gyms.index');
    }

	public function getGyms(Request $request)
	{
		$query = Gym::select(
			'id',
			'title',
			'instagram',
            'status'
		);

		$results = datatables($query)
			->addColumn('operation', function ($gym){
			   return view('editorArea.gyms.partials._operation', [
			   	'gym_id' => $gym->id
			   ]);
			})
			->addColumn('status', function ($gym){
				if ($gym->status){
					return '<span class="label label-success">' . __('mm.public.active') . '</span>';
				}else{
					return '<span class="label label-danger">' . __('mm.public.inactive') . '</span>';
				}
			})
			->rawColumns(['status', 'operation'])
			->make(true);

		return $results;
    }

    public function create()
    {
        return view('editorArea.gyms.create');
    }

    public function store(Request $request)
    {
    	$this->validation($request);
        $data = $request->all();
        if ($request->has('status')){
            $data['status'] = 1;
        }else{
            $data['status'] = 0;
        }
        $gym = Gym::create($data);
        if ($gym) {
	        /*save images*/
	        $images = (array)$request->file('images');
	        $counter = 0;
	        foreach ($images as $image) {
		        if ($counter < 5){
			        $image_path = $this->storeMedia($image, 'picture');
			        if ($image_path){
				        Addable::create([
					        'addable_id' => $gym->id,
					        'addable_type' => 'App\Models\Study\Gym',
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
					        'addable_id' => $gym->id,
					        'addable_type' => 'App\Models\Study\Gym',
					        'media_path' => $video_path,
					        'category' => 'VIDEO',
				        ]);
			        }
		        }
		        $counter++;
	        }
	        /*\ save videos*/


	        Session::flash('alert-info', 'success,' . __('mm.popup.add.success', ['name' => __('study.gym.singular')]));
            return redirect()->route('admin.gyms.index');
        };
        Session::flash('alert-info', 'error,' . __('mm.popup.add.error', ['name' => __('study.gym.singular')]));
        return redirect()->back();
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $categories = Category::all();
        $gym = Gym::find($id);
        return view('editorArea.gyms.update', compact('gym', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $this->validation($request);
        $data = $request->all();
        if ($request->has('status')){
            $data['status'] = 1;
        }else{
            $data['status'] = 0;
        }
        $gym = Gym::findOrFail($id);
        $update = $gym->update($data);
        if ($update) {
	        /*save images*/
	        $images = (array)$request->file('images');
	        $counter = 0;
	        foreach ($images as $image) {
		        if ($counter < 5){
			        $image_path = $this->storeMedia($image, 'picture');
			        if ($image_path){
				        Addable::create([
					        'addable_id' => $gym->id,
					        'addable_type' => 'App\Models\Study\Gym',
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
					        'addable_id' => $gym->id,
					        'addable_type' => 'App\Models\Study\Gym',
					        'media_path' => $video_path,
					        'category' => 'VIDEO',
				        ]);
			        }
		        }
		        $counter++;
	        }
	        /*\ save videos*/


	        Session::flash('alert-info', 'success,' . __('mm.popup.update.success', ['name' => __('study.gym.singular')]));
            return redirect()->back();
        }
        Session::flash('alert-info', 'error,' . __('mm.popup.update.error', ['name' => __('study.gym.singular')]));
        return redirect()->back();
    }

    public function destroy($id)
    {
    	$gym = Gym::findOrFail($id);
    	$addables = $gym->addables;
        if ($gym->delete()) {
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
