<?php

require_once(ROOT_DIR . 'lib/WebService/namespace.php');
require_once(ROOT_DIR . 'WebServices/Responses/TelegramResponse.php');
require_once(ROOT_DIR . 'Domain/TelegramToken.php');
require_once(ROOT_DIR . 'Domain/Access/TelegramTokenRepository.php');
require_once(ROOT_DIR . 'lib/Email/Messages/TelegramSignupEmail.php');

class TelegramWebService 
{
	/**
	 * @var IRestServer
	 */
	private $server;

	private $userRepository;
	private $telegram_api_token;

	private $response;

	public function __construct(IRestServer $server, IUserRepository $userRepository)
	{
		$this->server = $server;
		$this->userRepository = $userRepository;
		$this->tokenRepository = new TelegramTokenRepository();
		$this->telegram_api_token = Configuration::Instance()->GetSectionKey("telegram", "token");
		$this->message = $this->server->GetRequest()->message;
		$this->response = new TelegramResponse("sendMessage", ["parse_mode" => "Markdown"]);

		$this->user_token = $this->tokenRepository->GetByChatId($this->message->from->id);
		if($this->user_token !== null)
		{
			$this->user = $this->userRepository->FindByEmail($this->user_token->UserEmail());
		}
	}

	public function CheckToken()
	{
		return $this->server->GetQueryString("token") === $this->telegram_api_token;
	}

	public function GetUpdate()
	{
		if(!$this->CheckToken()) {
			$this->server->WriteResponse(RestResponse::Unauthorized(), RestResponse::UNAUTHORIZED_CODE);
			return;
		}

		$this->response->addParam("chat_id", $this->message->from->id);

		if(isset($this->message->entities)) {
			foreach(array_filter($this->message->entities, function($entity) { return $entity->type === "bot_command";}) as $entity) {
				$cmd = substr($this->message->text, $entity->offset + 1, $entity->length - 1);
				$params = substr($this->message->text, $entity->offset + $entity->length + 1);
				if(method_exists($this, $cmd))
				{
					$this->$cmd(current(explode("/", $params)));
				}
			}
		}

		$this->server->WriteResponse($this->response, 200);
	}

	public function signup($user_email)
	{
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
			$this->response->addParam("text", "I have send you an email with further instruction on how to validate your account. Please check your email inbox.");
		}
		else
		{
			$this->response->addParam("text", "Please send me a valid charite.de email address with this command.");
		}
	}

	public function start($token_token)
	{
		$token = $this->tokenRepository->GetByToken($token_token);
		$user = $token && $this->userRepository->FindByEmail($token->UserEmail());
		if(!($token && $user && $token->isValid()))
		{
			$this->response->addParam("text", "Please tell me your charite.de email address to /signup for my booking services.");
		}
		else
		{
			$this->tokenRepository->Add(new TelegramToken($token->UserEmail(), $token->Token(), 0, $this->message->from->id));
			$this->response->addParam("text", "Great! You have been successfully signed up to my booking services. Feel free to ask me about available rooms to /book.");
		}
	}

	public function book($options)
	{
		if(!$this->user instanceof User)
		{
			$this->response->addParam("text", "You have to be signed up to use my services. Please use /signup _your.email.address@charite.de_ to sign up with your email.");
			return;
		}

		$this->response->addParam("text", "I'll give you the list later...");
	}
}