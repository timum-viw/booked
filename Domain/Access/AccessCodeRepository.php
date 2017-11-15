<?php
/**
Copyright 2013-2014 Stephen Oliver, Nick Korbel

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

require_once (ROOT_DIR . 'Domain/AccessCode.php');

class GetAllAccessCodesCommand extends SqlCommand
{
    const GET_ALL_TELEGRAM_TOKENS = 'SELECT user_email, code, valid_until FROM authorization_codes';

    public function __construct()
    {
        parent::__construct(self::GET_ALL_TELEGRAM_TOKENS);
    }
}

class GetAccessCodeByUserEmailCommand extends SqlCommand
{
    const GET_TELEGRAM_TOKEN_BY_USER_EMAIL = "SELECT user_email, code, valid_until FROM authorization_codes WHERE user_email = @user_email";

    public function __construct($user_email)
    {
        parent::__construct(self::GET_TELEGRAM_TOKEN_BY_USER_EMAIL);
        $this->AddParameter(new Parameter("@user_email", $user_email));
    }
}

class GetAccessCodeByCodeCommand extends SqlCommand
{
    const GET_TELEGRAM_TOKEN_BY_TOKEN = "SELECT user_email, code, valid_until FROM authorization_codes WHERE code = @code";

    public function __construct($code)
    {
        parent::__construct(self::GET_TELEGRAM_TOKEN_BY_TOKEN);
        $this->AddParameter(new Parameter("@code", $code));
    }
}

class AddAccessCodeCommand extends SqlCommand
{
    const ADD_TELEGRAM_TOKEN = "REPLACE INTO `authorization_codes` (`user_email`, `code`, `valid_until`) VALUES (@user_email, @code, @valid_until);";

    public function __construct($code)
    {
        parent::__construct(self::ADD_TELEGRAM_TOKEN);
        $this->AddParameter(new Parameter("@user_email", $code->UserEmail()));
        $this->AddParameter(new Parameter("@code", $code->Code()));
        $this->AddParameter(new Parameter("@valid_until", $code->ValidUntil()));
    }
}

class DeleteAccessCodeCommand extends SqlCommand
{
    const DELETE_TELEGRAM_TOKEN = "DELETE FROM `authorization_codes` WHERE user_email = @user_email;";

    public function __construct($code)
    {
        parent::__construct(self::DELETE_TELEGRAM_TOKEN);
        $this->AddParameter(new Parameter("@user_email", $code->UserEmail()));
    }
}

class AccessCodeRepository
{

    private function codesFromReader($reader)
    {
        $codes = [];
        while ($row = $reader->GetRow())
        {
            $codes[] = AccessCode::FromRow($row);
        }
        $reader->Free();
        return $codes;
    }

    private function uniqueCodeFromReader($reader)
    {
        $codes = $this->codesFromReader($reader);
        if(sizeof($codes) > 1) {
            throw new \Exception("duplicate user_email entries in authorization_codes");
        }

        return $codes[0] ?? null;
    }

	public function GetAll()
    {
        $reader = ServiceLocator::GetDatabase()->Query(new GetAllAccessCodesCommand());

        return $this->codesFromReader($reader);
    }

    public function AddForUserEmail($user_email)
    {
        $code = AccessCode::Create($user_email);
        $this->Add($code);
        return $code;
    }

    public function Add(AccessCode $code)
    {
        ServiceLocator::GetDatabase()->ExecuteInsert(new AddAccessCodeCommand($code));
    }

    public function GetByUserEmail($user_email)
    {
        $reader = ServiceLocator::GetDatabase()->Query(new GetAccessCodeByUserEmailCommand($user_email));

        return $this->uniqueCodeFromReader($reader);
    }

    public function GetByCode($code)
    {
        $reader = ServiceLocator::GetDatabase()->Query(new GetAccessCodeByCodeCommand($code));

        return $this->uniqueCodeFromReader($reader);
    }

    public function Delete($code)
    {
        ServiceLocator::GetDatabase()->Query(new DeleteAccessCodeCommand($code));
    }
}