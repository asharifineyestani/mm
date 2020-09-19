<?php

namespace App\Http\Controllers\Admin;

use App\Models\Ads;
use App\Http\Requests\AdsRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class AdsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ads = Ads::all();
        return view('admin.ads.index', compact('ads'));
    }

    public function table(Request $request)
    {
        $config = new \stdClass();
        $config->routeName = 'admin.ads';
        $config->table = 'ads';
        $config->buttons = ['edit' => true, 'destroy' => true];


        $query = Ads::select('*');

        $results = datatables($query)
            ->addColumn('operation', function ($request) use ($config) {
                return view('admin.ads.operation', [
                    'config' => $config,
                    'object_id' => $request->id,
                ]);
            })
            ->editColumn('status', function ($request) use ($config) {
                return view('admin.ads.status')->with(
                    [
                        'object_table' => $config->table,
                        'object_id' => $request->id,
                        'object_field' => 'status',
                        'object_value' => $request->status,
                    ]);
            })
            ->editColumn('image', function ($request) {
                return view('_components.operations.image')->with(
                    [
                        'src' => $request->image,
                        'alt' => $request->title,
                    ]);
            })
            ->rawColumns(['operation', 'status', 'image'])
            ->make(true);

        return $results;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.ads.single');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(AdsRequest $request)
    {
        $this->maxAllowedWidth=1226;
        $data = $request->all();
        $data['image'] = $this->storeMedia($request->file('image')[0], 'picture');
        if (Ads::create($data)) {
            Session::flash('alert-info', 'success,' . __('mm.popup.add.success', ['name' => __('mm.ad.singular')]));
            return redirect()->route('admin.ads.index');
        }
        Session::flash('alert-info', 'success,' . __('mm.popup.add.error', ['name' => __('mm.ad.singular')]));
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $ad = Ads::find($id);
        return view('admin.ads.single', compact('ad'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'url' => 'required',
            'status' => 'required'
        ]);
        $ad = Ads::find($id);
        $data = $request->all();
        $this->maxAllowedWidth=1226;
        if ($request->hasFile('image')) {
            $this->unlinkMedia($ad->image);
            $data['image'] = $this->storeMedia($request->file('image')[0], 'picture');
        }

        if ($ad->update($data)) {
            Session::flash('alert-info', 'success,' . __('mm.popup.update.success', ['name' => __('mm.ad.singular')]));
            return redirect()->route('admin.ads.index');

        }
        Session::flash('alert-info', 'error,' . __('mm.popup.update.error', ['name' => __('mm.ad.singular')]));
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ad = Ads::find($id);

        if (Ads::destroy($id)) {

            $this->unlinkMedia($ad->image);
            die(true);
        }
        die(false);
    }


    public function changeStatus($id, $field, $status)
    {

        $allowed_statuses = [
            'hide' => -1,
            'show' => 1
        ];

        $allowedـfields = [
            'status' => 'status'
        ];

        \DB::table('ads')->where('id', $id)->update([$allowedـfields[$field] => $allowed_statuses[$status]]);

        return $allowed_statuses[$status];


    }
}
