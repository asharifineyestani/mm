<?php

namespace App;

use App\Helpers\Sh4Helper;
use App\Models\Body;
use App\Models\City;
use App\Models\Coach;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Request;
use App\Models\Role;
use Hekmatinasser\Verta\Facades\Verta;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Carbon\Carbon;
use App\Models\Credit;
use App\Models\CreditLog;
use Laravel\Passport\HasApiTokens;
use App\Models\Email;


/**
 * @property mixed credit
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $appends = ['name', 'age', 'roles', 'thumbnail', 'image', 'user_rank', 'rank'];

    protected $fillable = ['description', 'avatar', 'first_name', 'last_name', 'mobile', 'gender', 'city_id', 'phone',
        'birth_day', 'blood_group', 'introduction_method', 'password', 'email', 'sms', 'setting', 'status', 'reagent_id', 'country_id', 'created_at'];

    protected $hidden = [
        'password', 'remember_token', 'avatar'
    ];

    protected $casts = [
        'settings' => 'array',
    ];


    public function roles()
    {

        return $this->belongsToMany(Role::class);
    }

    public function getRolesAttribute()
    {
        return $this->roles()->pluck('name');
    }


    public function isRole($role)
    {
        if (in_array($role, $this->roles->toArray()))
            return true;
        else
            return false;
    }


    public function reagent()
    {
        return $this->belongsTo(User::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getAgeAttribute()
    {
        if ($this->birth_day)
            return Carbon::parse($this->birth_day)->age;
    }


    public function bodies()
    {
        return $this->hasMany(Body::class);
    }

    public function getBalanceAttribute()
    {
        return $this->credit->balance;
    }


    public function credit()
    {
        return $this->hasOne(Credit::class)->withDefault();
    }


    public function transactions()
    {
        return $this->hasManyThrough(CreditLog::Class)->latest();
    }

    public function coach()
    {
        return $this->hasOne(Coach::class);
    }


    public function canWithdraw($amount)
    {
        return $this->balance >= $amount;
    }


    public function deposit($amount, $type = 'deposit by programmer!?', $relatedId, $details = [], $accepted = true)
    {
        if ($accepted) {
            $this->credit->balance += $amount;
            $this->credit->save();
        } elseif (!$this->credit->exists) {
            $this->credit->save();
        }
        $this->credit->transactions()
            ->create([
                'amount' => $amount,
                'tracking_code' => uniqid('sh4_'),
                'type' => $type,
                'accepted' => $accepted,
                'related_id' => $relatedId,
                'details' => $details
            ]);
    }


    public function failDeposit($amount, $type = 'deposit', $details = [])
    {
        $this->deposit($amount, $type, $details, false);
    }


    public function withdraw($amount, $type = 'withdraw by programmer!?', $relatedId, $details = [], $shouldAccept = true)
    {
        $accepted = $shouldAccept ? $this->canWithdraw($amount) : true;

        if ($accepted) {
            $this->credit->balance -= $amount;
            $this->credit->save();
        } elseif (!$this->credit->exists) {
            $this->credit->save();
        }

        $this->credit->transactions()
            ->create([
                'amount' => $amount,
                'tracking_code' => uniqid('sh4_'),
                'type' => $type,
                'accepted' => $accepted,
                'related_id' => $relatedId,
                'details' => $details
            ]);
    }


    public function forceWithdraw($amount, $type = 'withdraw', $details = [])
    {
        return $this->withdraw($amount, $type, $details, false);
    }


    public function actualBalance()
    {
        $credits = $this->credit->transactions()
            ->Where('type', 'like', 'DEPOSIT' . '%')
            ->where('accepted', 1)
            ->sum('amount');

        $debits = $this->credit->transactions()
            ->Where('type', 'like', 'WITHDRAW' . '%')
            ->where('accepted', 1)
            ->sum('amount');

        return $credits - $debits;
    }


    /**
     * @param $builder
     * @param string $role
     * @return mixed
     */
    public function scopeRoleIs($builder, $role = 'user')
    {
        if ($role == 'user')
            return $builder->whereDoesntHave('roles');

        $query = $builder->whereHas('roles', function ($q) use ($role) {
            $q->is($role);
        });

        if ($role == 'coach')
            $query = $query->leftJoin('coach_fields', 'coach_fields.user_id', '=', 'id');
        return $query;
    }

    public function formatBirthDay($format = 'j F Y')
    {
        return Verta::createTimestamp(strtotime($this->birth_day))->format($format);
    }


    public function getThumbnailAttribute($value)
    {
        if (is_file(public_path(Sh4Helper::createThumbnailPath($this->avatar)))) {
            return config('app.url') . Sh4Helper::createThumbnailPath($this->avatar);
        }


        return config('app.url') . '/images/avatars/thumbnail/default.png';
    }


    public function getImageAttribute($value)
    {

        if (is_file(public_path($this->avatar))) {
            return config('app.url') . Sh4Helper::createPicturePath($this->avatar);
        }

        return config('app.url') . '/images/avatars/default.png';
    }

    public function voice()
    {
        return $this->hasOne('App\Models\Voice');
    }

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'prices', 'user_id', 'plan_id')->withPivot('price');;
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }


    public function CheckQualifyForReward($request_id)
    {
        $type = 'DEPOSIT_REWARD';

        $log = CreditLog::where('type', $type)
            ->where('related_id', $request_id)
            ->where('credit_id', $this->credit->id)
            ->count();


        if ($log)
            return false;
        else
            return true;


    }


    /**
     * @return float|int
     *
     * #todo sh4: in attribute be hich onvan behine nist. be dalile time kam neveshte shod
     */
    public function getUserRankAttribute()
    {

        $avg = $this->requestsForScore()->avg('score');
        $actualUsersScore = $avg ?? 0;

        $coach = Coach::where('user_id', $this->id)->first();

        if ($coach) {
            $veto = $coach->veto_score;

            $veto = $this->adaptRank($veto);

            $scoreByUser = ($veto == 0) ? $actualUsersScore : $veto;

            return $scoreByUser;
        }

    }


    public function getDefaultRankAttribute($value)
    {
        return $this->adaptRank($value);
    }


    public function getRankAttribute()
    {
//        if ($this->user_rank == 0)
//            return $this->default_rank;
//
//        return ($this->user_rank + $this->default_rank) / 2;

        $coach = Coach::where('user_id', $this->id)->first();
        $admin_score = isset($coach->admin_score) ? $coach->admin_score : 0;
        $value = $this->user_rank + $admin_score;
        return $this->adaptRank($value);
    }


    public function adaptRank($number)
    {
        $number = ($number > 10) ? 10 : $number;
        $number = ($number < 0) ? 0 : $number;


        return $number;

    }


    public function requestsForScore()
    {
        return $this->hasMany(Request::class, 'coach_id', 'id')->where('score', '>', 0)
            ->select('id', 'score', 'coach_id');
    }


    public function workout()
    {
        return $this->hasMany(Email::class)->select('workout','sounds', 'user_id', 'created_at')->where('workout', '<>', null);
    }

    public function nutrition()
    {
        return $this->hasMany(Email::class)->select('nutrition', 'user_id', 'created_at')->where('nutrition', '<>', null);
    }


    public function sounds()
    {
        return $this->hasMany(Email::class)->select('sounds', 'user_id', 'created_at')->where('sounds', '<>', null);
    }

    public function analyze()
    {
        return $this->hasMany(Email::class)->select('analyze', 'user_id', 'created_at')->where('analyze', '<>', null);
    }


    public function myRequests()
    {
        return $this->hasMany(Request::class, 'user_id');
    }

    public function requestsForMe()
    {
        return $this->hasMany(Request::class, 'coach_id');
    }


    public function body()
    {
        return $this->hasOne(Body::class)->latest();
    }


    public function payment()
    {
        return $this->hasOne(Payment::class)->latest();
    }

}
