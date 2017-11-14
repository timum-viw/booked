<?php

require_once(ROOT_DIR . 'lib/WebService/namespace.php');
require_once(ROOT_DIR . 'WebServices/Responses/TelegramResponse.php');
require_once(ROOT_DIR . 'Domain/TelegramToken.php');
require_once(ROOT_DIR . 'Domain/Access/TelegramTokenRepository.php');
require_once(ROOT_DIR . 'lib/Email/Messages/TelegramSignupEmail.php');
require_once(ROOT_DIR . 'lib/external/php-jwt/JWT.php');

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
		$this->tokenRepository = new TelegramTokenRepository();
		$this->jwt_secret = Configuration::Instance()->GetSectionKey("authentication", "jwt.secret");
	}

	public function Signup()
	{
		$user_email = $this->server->GetQueryString("email");
		$validator = new RequiredEmailDomainValidator($user_email);
		$validator->Validate();
		if($validator->IsValid())
		{
			$user = $this->userRepository->FindByEmail($user_email);
			if(!$user) {
				list($firstname, $lastname) = explode(".", ucwords(str_replace(["-","@"], [" ","."], $user_email), " ."));
				$user = (new Registration())->Register($user_email, $user_email, $firstname, $lastname, Password::GenerateRandom(), null, "en_us", null);
			}

			$token = TelegramToken::Create($user_email);
			$this->tokenRepository->Add($token);
			ServiceLocator::GetEmailService()->Send(new TelegramSignupEmail($user, $token->Token()));
		}
		else
		{
			$this->server->WriteResponse(new RestResponse(), RestResponse::BAD_REQUEST_CODE);
		}
	}

	public function Authorize()
	{
		$token_token = $this->server->GetQueryString("token");
		$token = $this->tokenRepository->GetByToken($token_token);
		$user = $token && $this->userRepository->FindByEmail($token->UserEmail());
		if($token && $user && $token->isValid())
		{
			$res = new RestResponse();
			$access_token = [
				"user_id" => $this->userRepository->FindByEmail($token->UserEmail())->Id(),
			];
			$res->access_token = \Firebase\JWT\JWT::encode($access_token, $this->jwt_secret);
			$this->tokenRepository->Delete($token);
			$this->server->WriteResponse($res);
		}
		else
		{
			$this->server->WriteResponse(RestResponse::NotFound(), RestResponse::NOT_FOUND_CODE);
		}
	}
}