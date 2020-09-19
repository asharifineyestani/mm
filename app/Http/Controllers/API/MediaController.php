<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\MediaRequest;
use App\Models\Addable;
use App\Models\Body;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use App\Facades\ResultData as Result;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public $defaultClass = 'NOT-ALLOCATED';
    public $defaultId = 0;
    public $data;
    public $prefix = 'api-bdy';


    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(MediaRequest $request)
    {




        Log::emergency($request->all()); #todo test


        if (Input::hasFile('pictures'))
            foreach (Input::file('pictures') as $media) {
                $mediaPath = $this->storeMedia($media, 'PICTURE');
                $addable = Addable::create([
                    'media_path' => $mediaPath,
                    'addable_id' => $this->defaultId,
                    'addable_type' => $this->defaultClass,
                    'category' => 'PICTURE',
                ]);
                if ($addable)
                    $this->data['media_paths'][] = $mediaPath;
            }


        if (Input::hasFile('videos')) {
            foreach (Input::file('videos') as $media) {
                $mediaPath = $this->storeMedia($media, 'VIDEO');
                $addable = Addable::create([
                    'media_path' => $mediaPath,
                    'addable_id' => $this->defaultId,
                    'addable_type' => $this->defaultClass,
                    'category' => 'VIDEO',
                ]);
                if ($addable)
                    $this->data['media_paths'][] = $mediaPath;
            }

        }

        return Result::setData($this->data)->get();
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public
    function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public
    function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public
    function destroy($id)
    {
        //
    }
}
