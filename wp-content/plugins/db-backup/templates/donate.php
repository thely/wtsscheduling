<?php
/***
* Description: Template For Donation
* Author: Syed Amir Hussain
***/
?>
<p>
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
		<input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="ZB42KXQRV72QQ">
		<p class="am_bold">
			Please donate us to help you better.
		</p>
		<fieldset class="am_donate">
			<legend><input type="hidden" name="on0" value="Donation Amount">Donation Amount</legend>
			<table>
			<tr>
				<td>
					<select name="os0">
						<option value="Tiny">Tiny $2.00 USD</option>
						<option value="Very Small">Very Small $5.00 USD</option>
						<option value="Small">Small $10.00 USD</option>
						<option value="Medium">Medium $20.00 USD</option>
						<option value="Large">Large $30.00 USD</option>
					</select>
				</td>
			</tr>
			</table>
			<input type="hidden" name="currency_code" value="USD"><input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online."><img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1">
		</form>
	</fieldset>
</p>