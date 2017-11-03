<?php

/**
 * Copyright 2017 Nick Korbel
 *
 * This file is part of Booked Scheduler.
 *
 * Booked Scheduler is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Booked Scheduler is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Booked Scheduler.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once(ROOT_DIR . 'Presenters/Authentication/LoginRedirector.php');

class ExternalAuthLoginPresenter
{
	/**
	 * @var ExternalAuthLoginPage
	 */
	private $page;
	/**
	 * @var IWebAuthentication
	 */
	private $authentication;
	/**
	 * @var IRegistration
	 */
	private $registration;

	public function __construct(ExternalAuthLoginPage $page, IWebAuthentication $authentication, IRegistration $registration)
	{
		$this->page = $page;
		$this->authentication = $authentication;
		$this->registration = $registration;
	}

	public function PageLoad()
	{
		if(isset($_GET['error'])) {
			$this->page->ShowError([$_GET['error']]);
		}

		if ($this->page->GetType() == 'google')
		{
			$profile = $this->GetSocialProfile('http://www.social.twinkletoessoftware.com/googleprofile.php');
			
		}
		if ($this->page->GetType() == 'fb')
		{
			$profile = $this->GetSocialProfile('http://www.social.twinkletoessoftware.com/fbprofile.php');
		}
		if ($this->page->GetType() == 'llp')
		{
			$token = $this->GetLLPAccessToken();
			if(!$token) {
				$this->page->ShowError(['access token could not be retrieved']);
			}
			$profile = $this->GetLLPProfile($token->access_token);
		}

		if($profile) {
			$this->ProcessSocialSingleSignOn($profile);
		} else {
			$this->page->ShowError(['no profile found']);
		}
	}

	private function GetSocialProfile($page)
	{
		$code = $_GET['code'];
		Log::Debug('Logging in with social. Code=%s', $code);
		$result = file_get_contents("$page?code=$code");
		return json_decode($result);
	}

	private function GetLLPAccessToken() {
		$code = $_GET['code'];
		$client_id = Configuration::Instance()->GetSectionKey('oauth', 'llp.client_id');
		$redirect_uri = Configuration::Instance()->GetSectionKey('oauth', 'llp.redirect_uri');
		$token_uri = Configuration::Instance()->GetSectionKey('oauth', 'llp.token_uri');
		$client_secret = Configuration::Instance()->GetSectionKey('oauth', 'llp.client_secret');
		$ch = curl_init();
		$url = "$token_uri?grant_type=authorization_code&code=$code&redirect_uri=$redirect_uri&client_id=$client_id&client_secret=$client_secret";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return $httpcode === 200 ? json_decode($response) : false;
	}

	private function GetLLPProfile($token)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'llp/zend/mobil/studi');
		$headr[] = 'Content-type: application/json';
		$headr[] = 'Authorization: Bearer '.$token;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if($httpcode === 200) {
			$profile = json_decode($response)->student_data;
			[$profile->first_name, $profile->last_name] = explode(" ", $profile->name);
			return $profile;
		} else {
			return false;
		}
	}

	private function ProcessSocialSingleSignOn($profile)
	{
		$requiredDomainValidator = new RequiredEmailDomainValidator($profile->email);
		$requiredDomainValidator->Validate();
		if (!$requiredDomainValidator->IsValid())
		{
			Log::Debug('Social login with invalid domain. %s', $profile->email);
			$this->page->ShowError(array(Resources::GetInstance()->GetString('InvalidEmailDomain')));
			return;
		}

		Log::Debug('Social login successful. Email=%s', $profile->email);
		$this->registration->Synchronize(new AuthenticatedUser($profile->email,
															   $profile->email,
															   $profile->first_name,
															   $profile->last_name,
															   Password::GenerateRandom(),
															   Resources::GetInstance()->CurrentLanguage,
															   Configuration::Instance()->GetDefaultTimezone(),
															   null,
															   null,
															   null));

		$this->authentication->Login($profile->email, new WebLoginContext(new LoginData()));
		LoginRedirector::Redirect($this->page);
	}
}
