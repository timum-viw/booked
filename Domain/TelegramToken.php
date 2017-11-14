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


class TelegramToken
{
    const DEFAULT_VALID_UNTIL = 3600;

    private $user_email;
    private $token;
    private $valid_until;
    private $chat_id;

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
    public function Token()
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function ValidUntil()
    {
        return $this->valid_until;
    }

    public function ChatId()
    {
        return $this->chat_id;
    }


    public function __construct($user_email, $token, $valid_until, $chat_id = null)
    {
        $this->user_email = $user_email;
        $this->token = $token;
        $this->valid_until = $valid_until;
        $this->chat_id = $chat_id;
    }

    public static function Create($user_email, $valid_until = self::DEFAULT_VALID_UNTIL)
    {
        $token = bin2hex(random_bytes(16));
        $valid_until = time() + $valid_until;
        return new TelegramToken($user_email, $token, $valid_until);
    }

    public static function FromRow($row)
    {
        return new TelegramToken(
            $row["user_email"],
            $row["token"],
            $row["valid_until"],
            $row["chat_id"]
        );
    }

    public function IsValid()
    {
        return $this->valid_until >= time();
    }

    public function hasChat()
    {
        return $this->chat_id !== null;
    }
}