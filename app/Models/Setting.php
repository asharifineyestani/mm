<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'value', 'locale', 'group', 'type'];

    protected $appends = ['title', 'body'];

	public function getValue()
	{
		if ($this->type == 'json'){
			return json_decode($this->value, true);
		}

		return $this->value;
    }

	public function getTitleAttribute()
	{
		return $this->getValue()['title'] ?? '';
    }

	public function getBodyAttribute()
	{
		return $this->getValue()['body'] ?? '';
    }
}
