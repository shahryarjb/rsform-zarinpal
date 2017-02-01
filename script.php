<?php
/**
 * @package     Joomla - > Site and Administrator payment info
 * @subpackage  com_Rsfrom
 * @subpackage 	trangell_Zarinpal
 * @copyright   trangell team => https://trangell.com
 * @copyright   Copyright (C) 20016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class plgSystemRSFPTrangellZarinpalInstallerScript
{
	public function preflight($type, $parent) {
		if ($type != 'uninstall') {
			$app = JFactory::getApplication();
			
			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/rsform.php')) {
				$app->enqueueMessage('Please install the RSForm! Pro component before continuing.', 'error');
				return false;
			}
			
			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/version.php')) {
				$app->enqueueMessage('Please upgrade RSForm! Pro to at least R45 before continuing!', 'error');
				return false;
			}
			
			if (!file_exists(JPATH_PLUGINS.'/system/rsfppayment/rsfppayment.php')) {
				$app->enqueueMessage('Please install the RSForm! Pro Payment Plugin first!', 'error');
				return false;
			}
			
			$jversion = new JVersion();
			if (!$jversion->isCompatible('3.6.0')) {
				$app->enqueueMessage('Please upgrade to at least Joomla! 3.6.x before continuing!', 'error');
				return false;
			}
		}
		
		return true;
	}
	
	public function postflight($type, $parent) {
		if ($type == 'update') {
			$db = JFactory::getDbo();
			$db->setQuery("SELECT * FROM #__rsform_config WHERE `SettingName`='trangellzarinpal.return'");
			if (!$db->loadResult()) {
				$db->setQuery("INSERT IGNORE INTO `#__rsform_config` (`SettingName`, `SettingValue`) VALUES ('trangellzarinpal.return', '')");
				$db->execute();
			}
		}
	}
}
