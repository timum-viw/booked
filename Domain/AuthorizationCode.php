<?php
/**
Copyright 2013-2017 Vincent Wyszynski

This file is part of Booked Scheduler.

Booked Scheduler is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Booked Scheduler is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Booked Scheduler.  If not, see <http://www.gnu.org/licenses/>.
 */


class AuthorizationCode
{
    const DEFAULT_VALID_UNTIL = 3600;

    private $user_email;
    private $code;
    private $valid_until;

    /**
     * @return string
     */
    public function UserEmail()
    {
        return $this->user_email;
    }

    /**
     * @return string
     */
    public function Code()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function ValidUntil()
    {
        return $this->valid_until;
    }

    public function __construct($user_email, $code, $valid_until)
    {
        $this->user_email = $user_email;
        $this->code = $code;
        $this->valid_until = $valid_until;
    }

    public static function Create($user_email, $valid_until = self::DEFAULT_VALID_UNTIL)
    {
        $code = bin2hex(random_bytes(16));
        $valid_until = time() + $valid_until;
        return new AuthorizationCode($user_email, $code, $valid_until);
    }

    public static function FromRow($row)
    {
        return new AuthorizationCode(
            $row["user_email"],
            $row["code"],
            $row["valid_until"]
        );
    }

    public function IsValid()
    {
        return $this->valid_until >= time();
    }
}