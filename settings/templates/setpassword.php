<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
style('settings', 'setpassword');
script('settings', 'setpassword');
?>

<form action="<?php print_unescaped($_['link']) ?>" id="set-password" method="post" autocapitalize="none">
	<div class="grouptop<?php if (!empty($_['invalidpassword'])) { echo ' shake'; } ?>">
		<label for="password" class="infield"><?php p($l->t('New password')); ?></label>
		<input type="password" name="password" id="password" value=""
				placeholder="<?php p($l->t('New Password')); ?>"
				autocomplete="new-password" autocorrect="off"
				required autofocus />
	</div>
	<div class="groupbottom<?php if (!empty($_['invalidpassword'])) {?> shake<?php } ?>">
		<input type="password" name="retypepassword" id="retypepassword" value=""
				autocomplete="new-password" autocorrect="off"
				placeholder="<?php p($l->t('Confirm Password')); ?>"/>
		<span id='message'></span>
	</div>
	<div class="submit-wrap">
		<label id="error-message" class="warning" style="display:none"></label>
		<input type="submit" id="submit" value="<?php p($l->t('Please set your password')); ?>" />
	</div>
</form>
