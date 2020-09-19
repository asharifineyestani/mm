<?php
/**
 * Created by PhpStorm.
 * User: ali
 * Date: 6/11/19
 * Time: 4:52 PM
 */

namespace App\Sh4Classes;


use Illuminate\Contracts\Hashing\Hasher as HashingContract;
use Illuminate\Hashing\HashManager;

class ShaHashing extends HashManager implements HashingContract {


    public function make($value, array $options = array()) {
        $value = env('SALT', '').$value;
        return sha1($value);
    }

    public function check($value, $hashedValue, array $options = array()) {
        return $this->make($value) === $hashedValue;
    }

    public function needsRehash($hashedValue, array $options = array()) {
        return false;
    }

    /**
     * Get information about the given hashed value.
     *
     * @param  string $hashedValue
     * @return array
     */
    public function info($hashedValue)
    {
        // TODO: Implement info() method.
    }


}
