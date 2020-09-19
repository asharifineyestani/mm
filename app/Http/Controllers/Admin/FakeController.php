<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Sh4Helper;
use App\Models\City;
use App\Models\Country;
use App\Models\Role;
use App\User;
use Hekmatinasser\Verta\Facades\Verta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class FakeController extends Controller
{
    private function validation(Request $request)
    {
        $request->validate([
                'first_name' => 'required | string',
                'last_name' => 'required | string',
                'gender' => 'required | in:MALE,FEMALE',
                'sms' => 'required',
                'status' => 'required'
            ]
        );
    }

    public function index()
    {
        $roles = Role::all();
        $cities = City::all();
        $countries = Country::all();
        return view('admin.fakes.index', compact('roles', 'cities', 'countries'));
    }

    public function getUsers(Request $request)
    {
        $query = User::roleIS('fake')
            ->select(DB::raw(
                'users.*,' .
                'CONCAT(users.first_name, " ", users.last_name) as full_name,' .
                'YEAR(CURRENT_TIMESTAMP) - YEAR(birth_day) - (RIGHT(CURRENT_TIMESTAMP, 5) < RIGHT(birth_day, 5)) as age'
            ))
            ->groupBy('users.id');
        $results = datatables($query)
            ->editColumn('gender', function ($request) {
                return __('mm.user.gender.' . $request->gender);
            })
            ->addColumn('operation', function ($request) {
                return view('admin.fakes.partials._operation', [
                    'user_id' => $request->id
                ]);
            })
            ->addColumn('operation', function ($request) {
                return view('admin.fakes.partials._operation', [
                    'user_id' => $request->id
                ]);
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
                return view('_components/operations/status')->with(
                    [
                        'object_value' => $request->status,
                        'object_id' => $request->id,
                        'object_table' => 'users',
                        'object_field' => 'status',
                    ]);
            })
            ->editColumn('created_at', function ($request) {
                return '<span class="small">' . Sh4Helper::formatPersianDate($request->created_at, 'j F Y') . '</span>';
            })
            ->filterColumn('full_name', function ($query, $keyword) {
                $sql = 'CONCAT(users.first_name, " ", users.last_name) like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('created_at', function ($query, $keyword) {
                $date = array_map('intval', explode('/', $this->arabicNumbers($keyword)));
                $date_g = verta()->setDate($date[0], $date[1], $date[2])->formatGregorian('Y-m-d');
                $query->whereDate('users.created_at', $date_g);
            })
            ->rawColumns(['sms', 'status', 'operation', 'created_at'])
            ->make(true);

        return $results;
    }

    public function create()
    {
        $roles = Role::all();
        $cities = City::all();
        $countries = Country::all();
        return view('admin.fakes.create', compact('roles', 'cities', 'countries'));
    }

    public function destroy($id)
    {
        DB::table('role_user')->where('user_id', '=', $id)->delete();
        if (User::destroy($id)) {
            die(true);
        }
        die(false);
    }

    /*show user dates for edit or show information*/
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $Url="";
	    if(!empty($user->avatar)){
            if(file_exists(public_path().$user->avatar)){
                $Url=$user->avatar;
            }
            else{
                $Url=url("/")."/images/avatars/default.png";
            }
        }
	    else
        {
            $Url=url("/")."/images/avatars/default.png";

        }
        $roles = Role::all();
	    $cities = City::all();
        $countries = Country::all();
        return view('admin.fakes.edit', compact('user', 'roles', 'cities', 'countries','Url'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'email' => 'required | string | email | unique:users,email,' . $id,
        ]);

        $data = $request->all();
        $user = User::find($id);
        if ($user->update($data)) {
	        Session::flash('alert-info', 'success,' . __('mm.popup.update.success', ['name' => __('mm.user.singular')]));
            return redirect()->back();
        }
        Session::flash('alert-info', 'error,' . __('mm.popup.update.error', ['name' => __('mm.user.singular')]));
        return redirect()->back();

    }

	/*store new user*/
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required | string | email | unique:users',
            'mobile' => 'required | string | unique:users',
            'password' => 'required| string',
        ]);

        $this->validation($request);
        $data = $request->all();
        unset($data['avatar']);
        $data['country_id'] = !empty($data['country_id']) ? $data['country_id'] : NULL;
        $data['city_id'] = !empty($data['city_id'])? $data['city_id'] : NULL;
        $data['birth_day'] = date('Y-m-d H:i:s', substr($data['birth_day'], 0, -3));
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        if ($user) {
	        // save avatar
	        if ($request->hasFile('avatar')){
		        $files = $request->file('avatar');
		        if (isset($files[0])){
			        $image_path = $this->storeMedia($files[0], 'picture');
			        $user->avatar = $image_path;
			        $user->save();
		        }
	        }
	        Session::flash('alert-info', 'success,' . __('mm.popup.add.success', ['name' => __('mm.user.singular')]));
            return redirect()->route('admin.fakes.index');
        }
        Session::flash('alert-info', 'success,' . __('mm.popup.add.error', ['name' => __('mm.user.singular')]));
        return redirect()->back();
    }

    protected function arabicNumbers($string)
    {
        $indian1 = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $indian2 = ['٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١', '٠'];
        $numbers = range(0, 9);
        $convertedIndian1 = str_replace($indian1, $numbers, $string);
        $englishNumbers = str_replace($indian2, $numbers, $convertedIndian1);

        return $englishNumbers;
    }

    public function reagent(Request $request)
    {
        $email = $request->get('reagent_email');
        $validator = Validator::make($request->all(), [
            'reagent_email' => 'exists:users,email', 'testValidator'
        ]);
        if ($validator->fails()){
            return response()->json(['status' => false, 'errors' => $validator->errors()]);
        }
        else
        {
            $id = User::where('email', $email)->first()->id;
            return response()->json(['status' => true, 'data' => ['id' => $id]]);
        }
    }

    public function changeStatus($id, $field , $status)
    {

        $allowed_statuses = [
            'hide' => -1,
            'show' => 1
        ];


        $allowedـfields = [
            'status' => 'status',
            'sms' => 'sms'
        ];


        \DB::table('users')->where('id', $id)->update([$allowedـfields[$field] => $allowed_statuses[$status]]);

        return back();

    }
}
