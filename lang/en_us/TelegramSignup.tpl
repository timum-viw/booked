<style type="text/css">
body {
	font-family: 'Roboto', sans-serif;
	margin: 15px;
	border: solid 1px grey;
}

pre {
	background-color: #ddd;
	border: solid 1px #444;
	border-radius: 2px;
}
</style>
<h3>Hi {$FirstName}!</h3>
<p>Somebody signed you up for the BhhCharite Telegram Bot.</p>
<p>To finish your registration and start booking please klick this link <a href="{$ActivationUrl}">{$ActivationUrl}</a>.</p>
<p>If this doesn't work pleas copy this line
<pre>
	/start {$ActivationUrl}
</pre>
<p>and paste it into your Chat with the BhhChariteBot.</p>
<p>If you still have problems just reply to this email and we will help you getting set up.</p>
