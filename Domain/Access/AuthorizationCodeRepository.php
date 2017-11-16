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

require_once (ROOT_DIR . 'Domain/AuthorizationCode.php');

class GetAllAuthorizationCodesCommand extends SqlCommand
{
    const GET_ALL_AUTHORIZATION_CODES = 'SELECT user_email, code, valid_until FROM authorization_codes';

    public function __construct()
    {
        parent::__construct(self::GET_ALL_AUTHORIZATION_CODES);
    }
}

class GetAuthorizationCodeByUserEmailCommand extends SqlCommand
{
    const GET_AUTHORIZATION_CODE_BY_USER_EMAIL = "SELECT user_email, code, valid_until FROM authorization_codes WHERE user_email = @user_email";

    public function __construct($user_email)
    {
        parent::__construct(self::GET_AUTHORIZATION_CODE_BY_USER_EMAIL);
        $this->AddParameter(new Parameter("@user_email", $user_email));
    }
}

class GetAuthorizationCodeByCodeCommand extends SqlCommand
{
    const GET_AUTHORIZATION_CODE_BY_TOKEN = "SELECT user_email, code, valid_until FROM authorization_codes WHERE code = @code";

    public function __construct($code)
    {
        parent::__construct(self::GET_AUTHORIZATION_CODE_BY_TOKEN);
        $this->AddParameter(new Parameter("@code", $code));
    }
}

class AddAuthorizationCodeCommand extends SqlCommand
{
    const ADD_AUTHORIZATION_CODE = "REPLACE INTO `authorization_codes` (`user_email`, `code`, `valid_until`) VALUES (@user_email, @code, @valid_until);";

    public function __construct($code)
    {
        parent::__construct(self::ADD_AUTHORIZATION_CODE);
        $this->AddParameter(new Parameter("@user_email", $code->UserEmail()));
        $this->AddParameter(new Parameter("@code", $code->Code()));
        $this->AddParameter(new Parameter("@valid_until", $code->ValidUntil()));
    }
}

class DeleteAuthorizationCodeCommand extends SqlCommand
{
    const DELETE_AUTHORIZATION_CODE = "DELETE FROM `authorization_codes` WHERE user_email = @user_email;";

    public function __construct($code)
    {
        parent::__construct(self::DELETE_AUTHORIZATION_CODE);
        $this->AddParameter(new Parameter("@user_email", $code->UserEmail()));
    }
}

class CleanUpAuthorizationCodeCommand extends SqlCommand
{
    const CLEAN_UP_AUTHORIZATION_CODE = "DELETE FROM `authorization_codes` WHERE valid_until < UNIX_TIMESTAMP();";

    public function __construct()
    {
        parent::__construct(self::CLEAN_UP_AUTHORIZATION_CODE);
    }
}

class AuthorizationCodeRepository
{

    private function codesFromReader($reader)
    {
        $codes = [];
        while ($row = $reader->GetRow())
        {
            $codes[] = AuthorizationCode::FromRow($row);
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
        $reader = ServiceLocator::GetDatabase()->Query(new GetAllAuthorizationCodesCommand());

        return $this->codesFromReader($reader);
    }

    public function AddForUserEmail($user_email)
    {
        $code = AuthorizationCode::Create($user_email);
        $this->Add($code);
        return $code;
    }

    public function Add(AuthorizationCode $code)
    {
        ServiceLocator::GetDatabase()->ExecuteInsert(new AddAuthorizationCodeCommand($code));
    }

    public function GetByUserEmail($user_email)
    {
        $reader = ServiceLocator::GetDatabase()->Query(new GetAuthorizationCodeByUserEmailCommand($user_email));

        return $this->uniqueCodeFromReader($reader);
    }

    public function GetByCode($code)
    {
        $reader = ServiceLocator::GetDatabase()->Query(new GetAuthorizationCodeByCodeCommand($code));

        return $this->uniqueCodeFromReader($reader);
    }

    public function Delete($code)
    {
        ServiceLocator::GetDatabase()->Query(new DeleteAuthorizationCodeCommand($code));
    }

    public function CleanUp()
    {
        ServiceLocator::GetDatabase()->Execute(new CleanUpAuthorizationCodeCommand());
    }
}