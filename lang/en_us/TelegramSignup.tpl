<style type="text/css">
body {
	font-family: 'Roboto', sans-serif;
	margin: 15px;
}

pre {
	text-align: center;
}

a {
	display: block;
	width: 100%;
	text-align: center;
	margin-top: 10px;
}

p {
	padding: 4px;
}
</style>
<h3>Hi {$FirstName}!</h3>
<p>Somebody signed you up for the BhhCharite Telegram Bot.</p>
<p>To finish your registration and start booking please click this link <a href="https://telegram.me/BhhChariteBot?start={$ActivationCode}">https://telegram.me/BhhChariteBot?start={$ActivationCode}</a></p>
<p>If this doesn't work please copy this line
<pre>
	/start {$ActivationCode}
</pre>
<p>and paste it into your chat with the BhhChariteBot.</p>
<p>If you still have problems just reply to this email and we will help you getting set up.</p>
<p>Have fun booking!</p>