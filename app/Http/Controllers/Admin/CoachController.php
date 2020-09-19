<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Sh4Helper;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Role;
use App\Models\Coach;
use App\User;
use App\Http\Requests\CoachRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Rules\Nationalcode;

class CoachController extends Controller
{
    public $passwordVeto = 999;
    private function validation(Request $request)
    {
        $request->validate([
                'first_name' => 'required | string',
                'last_name' => 'required | string',
                'gender' => 'required | in:MALE,FEMALE',
                'sms' => 'required',
                // 'city_id' => 'required',
                //'blood_group' => 'required',
                'status' => 'required',


            ]
        );
    }

    public function index()
    {
        return view('admin.coaches.index');
    }

    public function table(Request $request)
    {
        $config = new \stdClass();
        $config->routeName = 'admin.coaches';
        $config->table = 'coaches';
        $config->buttons = ['edit' => true, 'destroy' => true];

        $query = User::roleIs('coach')
            ->leftJoin('role_user', 'users.id', '=', 'role_user.user_id')
            ->leftJoin('roles', 'role_user.role_id', '=', 'roles.id')
            ->select(DB::raw(
                'users.*,' .
                'CONCAT(users.first_name, " ", users.last_name) as full_name, roles.name as role,coach_fields.visible as visible,' .
                'YEAR(CURRENT_TIMESTAMP) - YEAR(birth_day) - (RIGHT(CURRENT_TIMESTAMP, 5) < RIGHT(birth_day, 5)) as age'
            ))
            ->groupBy('users.id');

        $results = datatables($query)
            ->editColumn('gender', function ($request) {
                return __('mm.user.gender.' . $request->gender);
            })
            ->addColumn('operation', function ($request) use ($config) {
                return view('admin.ads.operation', [
                    'config' => $config,
                    'object_id' => $request->id,
                ]);
            })
            ->editColumn('role', function ($request) {
                $roles_array = explode(',', $request->role);
                $roles = '';
                if ($request->role) {
                    foreach ($roles_array as $role) {
                        $roles .= '<span class="label label-info ml-2">' . __('mm.user.rule')[$role] . '</span>';
                    }
                } else {
                    $roles = '<span class="label label-info ml-2">' . __('mm.user.rule')['user'] . '</span>';
                }

                return $roles;
            })
            ->editColumn('sms', function ($request) {
                return view('_components/operations/status')->with(
                    [
                        'object_value' => $request->sms,
                        'object_id' => $request->id,
                        'object_table' => 'users',
                        'object_field' => 'sms',
                    ]);
            })
            ->editColumn('status', function ($request) {
                return view('admin/coaches/partials/_dissable_message')->with(
                    [
                        'object_value' => $request->status,
                        'object_id' => $request->id,
                        'object_table' => 'users',
                        'object_field' => 'status',
                    ]);

                //_components/operations/status
                // return view('admin/coaches/partials/_dissable_message_modal');

            })
            ->editColumn('visible', function ($request) {
                return view('admin/coaches/partials/_visible')->with(
                    [
                        'object_value' => $request->visible,
                        'object_id' => $request->id,
                        'object_table' => 'coach_fields',
                        'object_field' => 'visible',
                    ]);

            })
            ->editColumn('created_at', function ($request) {
                return '<span class="small">' . Sh4Helper::formatPersianDate($request->created_at, 'j F Y') . '</span>';
            })
            ->filterColumn('full_name', function ($query, $keyword) {
                $sql = 'CONCAT(users.first_name, " ", users.last_name) like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->rawColumns(['sms', 'status', 'visible', 'operation', 'role', 'created_at'])
            ->make(true);

        return $results;
    }

    public function create()
    {
        $cities = City::all();
        $countries = Country::all();
        return view('admin.coaches.create', compact('cities', 'countries'));
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (User::destroy($id)) {
            $this->unlinkMedia($user->avatar);
            die(true);
        }
        die(false);
    }

    public function edit($id)
    {
        $passwordVeto = Input::get('veto');
        $veto = ($passwordVeto == $this->passwordVeto) ? true : false;
        $cities = City::all();
        $countries = Country::all();
        $coach = User::roleIs('coach')->where('id', $id)->firstOrFail();
        $Url="";
        if(!empty($coach->avatar)){
            if(file_exists(public_path().$coach->avatar)){
                $Url=$coach->avatar;
            }
            else{
                $Url=url("/")."/images/avatars/default.png";
            }
        }
        else
        {
            $Url=url("/")."/images/avatars/default.png";

        }
        return view('admin.coaches.edit', compact('coach', 'cities', 'countries', 'veto','Url'));
    }

    public function update(Request $request, $id)
    {
        $coach_fields = $request->get('coach');
        foreach ($coach_fields as $key => $value)
            if (empty($value))
                unset($coach_fields[$key]);
        $coach_fields["admin_score"] = empty($coach_fields["admin_score"])? 0 : $coach_fields["admin_score"];
        $coach_fields["veto_score"] = empty($coach_fields["veto_score"])? 0 : $coach_fields["veto_score"];
        $this->validation($request);
        $request->validate([
            'email' => 'required | string | email | unique:users,email,' . $id,
            'mobile' => 'required | string | unique:users,mobile,' . $id,
        ]);
      //  $data = $request->except(['password']);
        $data = $request->all();
        if ($request->get('password_veto') == $this->passwordVeto)
            $coach_fields["veto_score"] == $request->get('veto_score');
        else
            unset($coach_fields["veto_score"]);
        $birth_day = date('Y-m-d H:i:s', substr($data['birth_day'], 0, -3));
        $data['birth_day'] = $birth_day;
        $data['country_id'] = !empty($data['country_id']) ? $data['country_id'] : NULL;
        $data['city_id'] = !empty($data['city_id'])? $data['city_id'] : NULL;
        if (!isset($data['password'])) {
            unset($data['password']);
        }
        else if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);

        }
        $coach_fields['emergency_message']= !empty($coach_fields['emergency_message'])? $coach_fields['emergency_message'] : NULL;
        $user = User::find($id);
        // save avatar
        if (Input::hasFile('avatar')) {
            $this->unlinkMedia($user->avatar);
            $data['avatar'] = $this->storeMedia($request->file('avatar')[0], 'picture');
        }
        if ($user->update($data)) {
            Session::flash('alert-info', 'success,' . __('mm.popup.update.success', ['name' => __('mm.user.singular')]));
            if (is_null($user->coach)) {
                $user->coach()->save(new Coach($coach_fields));
            } else {
                $user->coach->update($coach_fields);
            }
            return redirect()->back();
        }
        Session::flash('alert-info', 'error,' . __('mm.popup.update.error', ['name' => __('mm.user.singular')]));
        return redirect()->back();
    }

    public function store(CoachRequest $request)
    {
        $coach_fields = $request->post('coach');
        $role = Role::where('name', 'coach')->first();
        foreach ($coach_fields as $key => $value)
            if (empty($value))
                unset($coach_fields[$key]);
        $this->validation($request);
        $data = $request->all();
        $data['birth_day'] = date('Y-m-d H:i:s', substr($data['birth_day'], 0, -3));
        $data['avatar'] = $this->storeMedia($request->file('avatar')[0], 'picture');
        $data['password'] = Hash::make($data['password']);
        $data['country_id'] = !empty($data['country_id']) ? $data['country_id'] : NULL;
        $data['city_id'] = !empty($data['city_id'])? $data['city_id'] : NULL;

        $user = User::create($data);
        $user->coach()->create($coach_fields);
        $user->roles()->attach($role);

        if ($user) {
            if (isset($data['roles'])) {
                $user->roles()->sync(array_values($data['roles']));
            }

            Session::flash('alert-info', 'success,' . __('mm.popup.add.success', ['name' => __('mm.user.singular')]));
            return redirect()->route('admin.coaches.index');
        }
        Session::flash('alert-info', 'success,' . __('mm.popup.add.error', ['name' => __('mm.user.singular')]));
        return redirect()->back();
    }

    public function messageDisabled(Request $request)
    {

        $user_id = $request->get('user_id');

        $message_for_disabled = $request->get('message_for_disabled');

        Coach::where('user_id', $user_id)
            ->update(['message_for_disabled' => $message_for_disabled]);

        return ('Success');
    }
    public function changeVisible($id, $field, $status)
    {

        if ($status == 'show') {
            $visible = 1;
        } else if ($status == 'hide') {
            $visible = -1;
        }

        Coach::where('user_id', $id)
            ->update([$field => $visible]);
        return back();
    }
}

