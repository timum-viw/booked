<?php

require_once(ROOT_DIR . 'lib/WebService/namespace.php');
require_once(ROOT_DIR . 'Domain/AccessCode.php');
require_once(ROOT_DIR . 'lib/Email/Messages/TelegramSignupEmail.php');

class TelegramWebService 
{
	/**
	 * @var IRestServer
	 */
	private $server;
	private $userRepository;

	public function __construct(IRestServer $server, IUserRepository $userRepository)
	{
		$this->server = $server;
		$this->userRepository = $userRepository;
	}

	/**
	 * @name SignUp
	 * @description sign up an user for Booked Scheduler with Telegram integration
	 * @return void
	 */
	public function Signup()
	{
		$request = $this->server->GetRequest();
		$user_email = $request->user_email;
		$validator = new RequiredEmailDomainValidator($user_email);
		$validator->Validate();
		if($validator->IsValid())
		{
			$user = $this->userRepository->FindByEmail($user_email);
			if(!$user) {
				list($firstname, $lastname) = explode(".", ucwords(str_replace(["-","@"], [" ","."], $user_email), " ."));
				$user = (new Registration())->Register($user_email, $user_email, $firstname, $lastname, Password::GenerateRandom(), null, "en_us", null);
			}

			$token = AccessCode::Create($user_email);
			$this->tokenRepository->Add($token);
			ServiceLocator::GetEmailService()->Send(new TelegramSignupEmail($user, $token->Token()));
		}
		else
		{
			$this->server->WriteResponse(new RestResponse(), RestResponse::BAD_REQUEST_CODE);
		}
	}

	public function Status()
	{
		if(Configuration::Instance()->GetSectionKey("telegram", "enabled"))
		{
			$response = new RestResponse();
			$response->telegram = true;
			$this->server->WriteResponse($response, RestResponse::OK_CODE);
		}
		else
		{
			$this->server->WriteResponse(RestResponse::Unauthorized(), RestResponse::UNAUTHORIZED_CODE);
		}
	}
}