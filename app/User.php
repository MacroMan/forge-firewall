<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $ip
 */
class User extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'ip',
    ];

    /**
     * @return User
     */
    public static function getUser(): User
    {
        $user = User::whereNotNull('name')->first();

        if (!$user) {
            $user = new User();
        }

        return $user;
    }

    /**
     * @param string $ip
     * @return bool If the save was successful
     */
    public function setIP(string $ip): bool
    {
        $this->ip = $ip;
        return $this->save();
    }

    /**
     * @return bool
     */
    public function isIPSet(): bool
    {
        return !!$this->ip;
    }
}
