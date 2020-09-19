<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class WizardFormMessageController extends Controller
{
    private function rules()
    {
        $rules = [
            'key' => "String|unique:settings,key",
            'title' => 'required|string',
            'message_body' => 'required|string',
            'locale' => 'required|string|in:fa,en',
            'group' => 'required|string',
            'type' => 'required|string'
        ];

        return $rules;
    }

    public function index()
    {
        return view('admin.wizard-form-messages.index');
    }

    public function getMessages(Request $request)
    {
        $query = Setting::where('group', 'message')
            ->orderBy('key', 'asc')
            ->get();

        $results= datatables($query)
            ->editColumn('body', function ($setting){
                $body = mb_strlen(strip_tags($setting->body)) > 50 ? mb_substr(strip_tags($setting->body), 0, 50) . '...' : strip_tags($setting->body);
                return '<section class="small">' . $body . '</section>';
            })
            ->addColumn('operation', function ($setting){
                return view('admin.wizard-form-messages.partials._operation', [
                    'setting_key' => $setting->key
                ]);
            })
            ->addColumn('title', function ($setting){
                return $setting->title;
            })
            ->addColumn('body', function ($setting){
                return $setting->body;
            })
            ->rawColumns(['body', 'operation'])
            ->make(true);

        return $results;
    }

    public function create()
    {
        return view('admin.wizard-form-messages.create');
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());
        $data = $request->all();
        $value = [];
        $value['key'] = $request->key;
        $value['title'] = $request->title;
        $value['body'] = $request->message_body;
        $data['value'] = json_encode($value);
        $setting = Setting::create($data);
        $save = $setting->save();

        if ($save){
            Session::flash('alert-info', 'success,' . __('mm.popup.add.success', ['name' => __('mm.public.wizard_form_message.singular')]));
        }

        return redirect()->route('admin.WizardFormMessages.index');
    }

    public function edit($key)
    {
        $setting = Setting::where('key',$key)->firstOrfail();
        return view('admin.wizard-form-messages.edit', [
            'setting' => $setting
        ]);
    }

    public function update(Request $request, $key)
    {
        $setting = Setting::where('key',$key)->firstOrfail();
        $request->validate($this->rules($key));
        //$data = $request->all();
        $value = [];
        $value['title'] = $request->title;
        $value['body'] = $request->message_body;
        $data['value'] = json_encode($value);
        $update = $setting->where('key',$key)->update($data);

        if ($update){
            Session::flash('alert-info', 'success,' . __('mm.popup.update.success', ['name' => __('mm.public.wizard_form_message.singular')]));
        }

        return redirect()->back();
    }

    public function destroy($key)
    {
        if (Setting::where('key',$key)->delete()){
            die(true);
        }else{
            die(false);
        }

    }
}
