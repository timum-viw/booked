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

require_once (ROOT_DIR . 'Domain/TelegramToken.php');

class GetAllTelegramTokensCommand extends SqlCommand
{
    const GET_ALL_TELEGRAM_TOKENS = 'SELECT user_email, token, valid_until, chat_id FROM telegram_tokens';

    public function __construct()
    {
        parent::__construct(self::GET_ALL_TELEGRAM_TOKENS);
    }
}

class GetTelegramTokenByUserEmailCommand extends SqlCommand
{
    const GET_TELEGRAM_TOKEN_BY_USER_EMAIL = "SELECT user_email, token, valid_until, chat_id FROM telegram_tokens WHERE user_email = @user_email";

    public function __construct($user_email)
    {
        parent::__construct(self::GET_TELEGRAM_TOKEN_BY_USER_EMAIL);
        $this->AddParameter(new Parameter("@user_email", $user_email));
    }
}

class GetTelegramTokenByTokenCommand extends SqlCommand
{
    const GET_TELEGRAM_TOKEN_BY_TOKEN = "SELECT user_email, token, valid_until, chat_id FROM telegram_tokens WHERE token = @token";

    public function __construct($token)
    {
        parent::__construct(self::GET_TELEGRAM_TOKEN_BY_TOKEN);
        $this->AddParameter(new Parameter("@token", $token));
    }
}

class GetTelegramTokenByChatIdCommand extends SqlCommand
{
    const GET_TELEGRAM_TOKEN_BY_CHAT_ID = "SELECT user_email, token, valid_until, chat_id FROM telegram_tokens WHERE chat_id = @chat_id";

    public function __construct($chat_id)
    {
        parent::__construct(self::GET_TELEGRAM_TOKEN_BY_CHAT_ID);
        $this->AddParameter(new Parameter("@chat_id", $chat_id));
    }
}


class AddTelegramTokenCommand extends SqlCommand
{
    const ADD_TELEGRAM_TOKEN = "REPLACE INTO `telegram_tokens` (`user_email`, `token`, `valid_until`, `chat_id`) VALUES (@user_email, @token, @valid_until, @chat_id);";

    public function __construct($token)
    {
        parent::__construct(self::ADD_TELEGRAM_TOKEN);
        $this->AddParameter(new Parameter("@user_email", $token->UserEmail()));
        $this->AddParameter(new Parameter("@token", $token->Token()));
        $this->AddParameter(new Parameter("@valid_until", $token->ValidUntil()));
        $this->AddParameter(new Parameter("@chat_id", $token->ChatId()));
    }
}

class DeleteTelegramTokenCommand extends SqlCommand
{
    const DELETE_TELEGRAM_TOKEN = "DELETE FROM `telegram_tokens` WHERE user_email = @user_email;";

    public function __construct($token)
    {
        parent::__construct(self::DELETE_TELEGRAM_TOKEN);
        $this->AddParameter(new Parameter("@user_email", $token->UserEmail()));
    }
}

class TelegramTokenRepository
{

    private function tokensFromReader($reader)
    {
        $tokens = [];
        while ($row = $reader->GetRow())
        {
            $tokens[] = TelegramToken::FromRow($row);
        }
        $reader->Free();
        return $tokens;
    }

    private function uniqueTokenFromReader($reader)
    {
        $tokens = $this->tokensFromReader($reader);
        if(sizeof($tokens) > 1) {
            throw new \Exception("duplicate user_email entries in telegram_tokens");
        }

        return $tokens[0] ?? null;
    }

	public function GetAll()
    {
        $reader = ServiceLocator::GetDatabase()->Query(new GetAllTelegramTokensCommand());

        return $this->tokensFromReader($reader);
    }

    public function AddForUserEmail($user_email)
    {
        $token = TelegramToken::Create($user_email);
        $this->Add($token);
        return $token;
    }

    public function Add(TelegramToken $token)
    {
        ServiceLocator::GetDatabase()->ExecuteInsert(new AddTelegramTokenCommand($token));
    }

    public function GetByUserEmail($user_email)
    {
        $reader = ServiceLocator::GetDatabase()->Query(new GetTelegramTokenByUserEmailCommand($user_email));

        return $this->uniqueTokenFromReader($reader);
    }

    public function GetByToken($token)
    {
        $reader = ServiceLocator::GetDatabase()->Query(new GetTelegramTokenByTokenCommand($token));

        return $this->uniqueTokenFromReader($reader);
    }

    public function GetByChatId($token)
    {
        $reader = ServiceLocator::GetDatabase()->Query(new GetTelegramTokenByChatIdCommand($token));

        return $this->uniqueTokenFromReader($reader);
    }

    public function Delete($token)
    {
        ServiceLocator::GetDatabase()->Query(new DeleteTelegramTokenCommand($token));
    }
}