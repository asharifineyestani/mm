<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\Setting;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Input;


class OptionController extends Controller
{
    public $accessCoach=1;
    protected function validation(Request $request){
        $request->validate([
            'value' => 'required'
        ]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $accessCoach= Input::get('accessCoach');
        if($accessCoach){
            $setting = Setting::where('key','except')
                ->first();
            $setting->value=json_decode($setting->value);
            return view('admin.options.access',compact('setting'));
        }
        else{
            $settings = Setting::where('type','!=','json')
                ->whereIn('key',array('reward','usd'))
                ->get();
            return view('admin.options.index',compact('settings'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.options.single');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validation($request);

        $setting = Setting::create($request->all());
        if ($setting) {
            Session::flash('alert-info', 'success,' . __('mm.popup.add.success', ['name' => __('mm.option.singular')]));
            return redirect()->route('admin.options.index');
        }
        else{
            Session::flash('alert-info', 'error,'.__('mm.popup.add.error',['name'=>__('mm.option.singular')]));
            return redirect()->route('admin.options.index');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $setting = Setting::where('id',$id)->first();
        return view('admin.options.single',compact('setting'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validation($request);
        $current_setting= Setting::find($id);
        $setting = Setting::find($id)->update($request->all());
        if ($setting) {
            /*save options*/
            Session::flash('alert-info', 'success,' . __('mm.popup.update.success', ['name' => __('mm.public.'.$current_setting->key.'')]));
            return redirect()->route('admin.options.index');
        }
        else{
            Session::flash('alert-info', 'error,'.__('mm.popup.update.error',['name'=>__('mm.public.'.$current_setting->key.'')]));
            return redirect()->route('admin.options.index');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Setting::destroy($id)) {
            die(true);
        }
        die(false);
    }
    public function FilterFemalAccessToCoach(Request $request,$id){
        $female= !empty($request->post('FEMALE'))? $request->post('FEMALE'): 0;
        $male= !empty($request->post('MALE'))? $request->post('MALE'): 0;
        $Value=array(
            'FEMALE' => $female,
            'MALE' => $male,

        );
        $current_setting= Setting::find($id);
        $data['value']=json_encode($Value);
        $setting = Setting::find($id)->update($data);
        if ($setting) {
            /*save options*/
            Session::flash('alert-info', 'success,' . __('mm.popup.update.success', ['name' => __('mm.public.'.$current_setting->key.'')]));
            return redirect()->route('admin.options.index');
        }
        else{
            Session::flash('alert-info', 'error,'.__('mm.popup.update.error',['name'=>__('mm.public.'.$current_setting->key.'')]));
            return redirect()->route('admin.options.index');
        }
    }
}
