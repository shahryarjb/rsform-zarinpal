<?php header("Content-type: text/html; charset=UTF-8"); ?>
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
defined('_JEXEC') or die('Restricted access');
if (!class_exists ('checkHack')) {
	require_once( JPATH_PLUGINS . '/system/rsfptrangellzarinpal/trangell_inputcheck.php');
}

class plgSystemRSFPTrangellZarinpal extends JPlugin {
	var $componentId = 200;
	var $componentValue = 'trangellzarinpal';
	
	public function __construct( &$subject, $config )
	{
		parent::__construct( $subject, $config );
		$this->newComponents = array(200);
	}
	
	function rsfp_bk_onAfterShowComponents() {
		$lang = JFactory::getLanguage();
		$lang->load('plg_system_rsfptrangellzarinpal');
		$db = JFactory::getDBO();
		$formId = JRequest::getInt('formId');
		$link = "displayTemplate('" . $this->componentId . "')";
		if ($components = RSFormProHelper::componentExists($formId, $this->componentId))
		   $link = "displayTemplate('" . $this->componentId . "', '" . $components[0] . "')";
?>
        <li class="rsform_navtitle"><?php echo 'درگاه زرین پال'; ?></li>
		<li><a href="javascript: void(0);" onclick="<?php echo $link; ?>;return false;" id="rsfpc<?php echo $this->componentId; ?>"><span id="TRANGELLZARINPAL"><?php echo JText::_('اضافه کردن درگاه زرین پال'); ?></span></a></li>
		
		
		<?php
		
	}
	
	function rsfp_getPayment(&$items, $formId) {
		if ($components = RSFormProHelper::componentExists($formId, $this->componentId)) {
			$data = RSFormProHelper::getComponentProperties($components[0]);
			$item = new stdClass();
			$item->value = $this->componentValue;
			$item->text = $data['LABEL'];
			// add to array
			$items[] = $item;
		}
	}
	
	function rsfp_doPayment($payValue, $formId, $SubmissionId, $price, $products, $code) {//test
	    $app	= JFactory::getApplication();
		// execute only for our plugin
		if ($payValue != $this->componentValue) return;
		$tax = RSFormProHelper::getConfig('trangellzarinpal.tax.value');
		if ($tax)
			$nPrice = round($tax,0) + round($price,0) ;
		else 
			$nPrice = round($price,0);
		if ($nPrice > 100) {
			$Amount = $nPrice/10; // Toman 
			$Description = 'خرید محصول از فروشگاه   '; 
			$Email = ''; 
			$Mobile = ''; 
			$CallbackURL = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId . '&task=plugin&plugin_task=trangellzarinpal.notify&code=' . $code;
				
			try {
				 $client = new SoapClient('https://www.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']); 	
				//$client = new SoapClient('https://sandbox.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']); // for local

				$result = $client->PaymentRequest(
					[
					'MerchantID' => RSFormProHelper::getConfig('trangellzarinpal.api'),
					'Amount' => $Amount,
					'Description' => $Description,
					'Email' => '',
					'Mobile' => '',
					'CallbackURL' => $CallbackURL,
					]
				);
				
				$resultStatus = abs($result->Status); 
				if ($resultStatus == 100) {
					// Header('Location: https://sandbox.zarinpal.com/pg/StartPay/'.$result->Authority); 
					$app->redirect('https://www.zarinpal.com/pg/StartPay/'.$result->Authority); 
				} else {
					$msg= $this->getGateMsg('error'); 
					$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
					$app->redirect($link, '<h2>'.$msg. $resultStatus. '</h2>', $msgType='Error'); 
				}
			}
			catch(\SoapFault $e) {
				$msg= $this->getGateMsg('error'); 
				$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
				$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
			}
		}
		else {
			$msg= $this->getGateMsg('price'); 
			$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
			$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
		}
	}
	
	function rsfp_bk_onAfterCreateComponentPreview($args = array()) {
		if ($args['ComponentTypeName'] == 'trangellzarinpal') {
			$args['out'] = '<td>&nbsp;</td>';
			$args['out'].= '<td>'.$args['data']['LABEL'].'</td>';
		}
	}
	
	function rsfp_bk_onAfterShowConfigurationTabs($tabs) {
		$lang = JFactory::getLanguage(); 
		$lang->load('plg_system_rsfptrangellzarinpal'); 
		$tabs->addTitle('تنظیمات درگاه زرین پال', 'form-TRANGELZARINPAL'); 
		$tabs->addContent($this->trangellzarinpalConfigurationScreen());
	}
  
	function rsfp_f_onSwitchTasks() {
		if (JRequest::getVar('plugin_task') == 'trangellzarinpal.notify') {
			$app	= JFactory::getApplication();
			$jinput = $app->input;
			$code 	= $jinput->get->get('code', '', 'STRING');
			$formId = $jinput->get->get('formId', '0', 'INT');
			$db 	= JFactory::getDBO();
			$db->setQuery("SELECT SubmissionId FROM #__rsform_submissions s WHERE s.FormId='".$formId."' AND MD5(CONCAT(s.SubmissionId,s.DateSubmitted)) = '".$db->escape($code)."'");
			$SubmissionId = $db->loadResult();
			//$mobile = $this::getPayerMobile ($formId,$SubmissionId);
			//===================================================================================
			$Authority = $jinput->get->get('Authority', '0', 'INT');
			$status = $jinput->get->get('Status', '', 'STRING');
			
			if (
				checkHack::checkString($status) &&
				checkHack::checkString($code)
			){
				if ($status == 'OK') {
					try {
						$client = new SoapClient('https://www.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']); 
						//$client = new SoapClient('https://sandbox.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']); // for local

						$result = $client->PaymentVerification(
							[
								'MerchantID' => RSFormProHelper::getConfig('trangellzarinpal.api'),
								'Authority' => $Authority,
								'Amount' => round($this::getPayerPrice ($formId,$SubmissionId),0)/10,
							]
						);
						$resultStatus = abs($result->Status); 
						if ($resultStatus == 100) {		
							if ($SubmissionId) {
								$db->setQuery("UPDATE #__rsform_submission_values sv SET sv.FieldValue=1 WHERE sv.FieldName='_STATUS' AND sv.FormId='".$formId."' AND sv.SubmissionId = '".$SubmissionId."'");
								$db->execute();
								$db->setQuery("UPDATE #__rsform_submission_values sv SET sv.FieldValue='"  . "کد پیگیری  "  . $result->RefID . "' WHERE sv.FieldName='transaction' AND sv.FormId='" . $formId . "' AND sv.SubmissionId = '" . $SubmissionId . "'");
								$db->execute();
								$mainframe = JFactory::getApplication();
								$mainframe->triggerEvent('rsfp_afterConfirmPayment', array($SubmissionId));
							}
							$msg= $this->getGateMsg($resultStatus); 
							$app->enqueueMessage($msg. '<br />' . ' کد پیگیری شما' . $result->RefID, 'message');	
						} 
						else {
							$msg= $this->getGateMsg($resultStatus); 
							$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
							$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
						}
					}
					catch(\SoapFault $e) {
						$msg= $this->getGateMsg('error'); 
						$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
						$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
					}
				}
				else {
					$msg= $this->getGateMsg(intval(17)); 
					$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
					$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
				}
			}
			else {
				$msg= $this->getGateMsg('hck2'); 
				$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
				$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 	
			}
		}
	}
	
	function trangellzarinpalConfigurationScreen() {
		ob_start();
?>
		<div id="page-trangellzarinpal" class="com-rsform-css-fix">
			<table  class="admintable">
				<tr>
					<td width="200" style="width: 200px;" align="right" class="key"><label for="api"><?php echo 'مرچند کد'; ?></label></td>
					<td><input type="text" name="rsformConfig[trangellzarinpal.api]" value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('trangellzarinpal.api')); ?>" size="100" maxlength="64"></td>
				</tr>
				<tr>
					<td width="200" style="width: 200px;" align="right" class="key"><label for="tax.value"><?php echo 'مقدار مالیات'; ?></label></td>
					<td><input type="text" name="rsformConfig[trangellzarinpal.tax.value]" value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('trangellzarinpal.tax.value')); ?>" size="4" maxlength="5"></td>
				</tr>
			</table>
		</div>
	
		<?php
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}
	
	function getGateMsg ($msgId) {
		switch($msgId){
			case	11: $out =  'شماره کارت نامعتبر است';break;
			case	12: $out =  'موجودي کافي نيست';break;
			case	13: $out =  'رمز نادرست است';break;
			case	14: $out =  'تعداد دفعات وارد کردن رمز بيش از حد مجاز است';break;
			case	15: $out =   'کارت نامعتبر است';break;
			case	17: $out =   'کاربر از انجام تراکنش منصرف شده است';break;
			case	18: $out =   'تاريخ انقضاي کارت گذشته است';break;
			case	21: $out =   'پذيرنده نامعتبر است';break;
			case	22: $out =   'ترمينال مجوز ارايه سرويس درخواستي را ندارد';break;
			case	23: $out =   'خطاي امنيتي رخ داده است';break;
			case	24: $out =   'اطلاعات کاربري پذيرنده نامعتبر است';break;
			case	25: $out =   'مبلغ نامعتبر است';break;
			case	31: $out =  'پاسخ نامعتبر است';break;
			case	32: $out =   'فرمت اطلاعات وارد شده صحيح نمي باشد';break;
			case	33: $out =   'حساب نامعتبر است';break;
			case	34: $out =   'خطاي سيستمي';break;
			case	35: $out =   'تاريخ نامعتبر است';break;
			case	41: $out =   'شماره درخواست تکراري است';break;
			case	42: $out =   'تراکنش Sale يافت نشد';break;
			case	43: $out =   'قبلا درخواست Verify داده شده است';break;
			case	44: $out =   'درخواست Verify يافت نشد';break;
			case	45: $out =   'تراکنش Settle شده است';break;
			case	46: $out =   'تراکنش Settle نشده است';break;
			case	47: $out =   'تراکنش Settle يافت نشد';break;
			case	48: $out =   'تراکنش Reverse شده است';break;
			case	49: $out =   'تراکنش Refund يافت نشد';break;
			case	51: $out =   'تراکنش تکراري است';break;
			case	52: $out =   'سرويس درخواستي موجود نمي باشد';break;
			case	54: $out =   'تراکنش مرجع موجود نيست';break;
			case	55: $out =   'تراکنش نامعتبر است';break;
			case	61: $out =   'خطا در واريز';break;
			case	100: $out =   'تراکنش با موفقيت انجام شد.';break;
			case	111: $out =   'صادر کننده کارت نامعتبر است';break;
			case	112: $out =   'خطاي سوئيچ صادر کننده کارت';break;
			case	113: $out =   'پاسخي از صادر کننده کارت دريافت نشد';break;
			case	114: $out =   'دارنده کارت مجاز به انجام اين تراکنش نيست';break;
			case	412: $out =   'شناسه قبض نادرست است';break;
			case	413: $out =   'شناسه پرداخت نادرست است';break;
			case	414: $out =   'سازمان صادر کننده قبض نامعتبر است';break;
			case	415: $out =   'زمان جلسه کاري به پايان رسيده است';break;
			case	416: $out =   'خطا در ثبت اطلاعات';break;
			case	417: $out =   'شناسه پرداخت کننده نامعتبر است';break;
			case	418: $out =   'اشکال در تعريف اطلاعات مشتري';break;
			case	419: $out =   'تعداد دفعات ورود اطلاعات از حد مجاز گذشته است';break;
			case	421: $out =   'IP نامعتبر است';break;
			case	500: $out =   'کاربر به صفحه زرین پال رفته ولي هنوز بر نگشته است';break;
			case	'1':
			case	'error': $out ='خطا غیر منتظره رخ داده است';break;
			case	'hck2': $out = 'لطفا از کاراکترهای مجاز استفاده کنید';break;
			case	'notff': $out = 'سفارش پیدا نشد';break;
			case	'price': $out = 'مبلغ وارد شده کمتر از ۱۰۰۰ ریال می باشد';break;
			default: $out ='خطا غیر منتظره رخ داده است';break;
		}
		return $out;
	}

	function getPayerMobile ($formId,$SubmissionId) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('FieldValue')
			->from($db->qn('#__rsform_submission_values'));
		$query->where(
			$db->qn('FormId') . ' = ' . $db->q($formId) 
							. ' AND ' . 
			$db->qn('SubmissionId') . ' = ' . $db->q($SubmissionId)
							. ' AND ' . 
			$db->qn('FieldName') . ' = ' . $db->q('mobile')
		);
		$db->setQuery((string)$query); 
		$result = $db->loadResult();
		return $result;
	}

	function getPayerPrice ($formId,$SubmissionId) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('FieldValue')
			->from($db->qn('#__rsform_submission_values'));
		$query->where(
			$db->qn('FormId') . ' = ' . $db->q($formId) 
							. ' AND ' . 
			$db->qn('SubmissionId') . ' = ' . $db->q($SubmissionId)
							. ' AND ' . 
			$db->qn('FieldName') . ' = ' . $db->q('price')
		);
		$db->setQuery((string)$query); 
		$result = $db->loadResult();
		return $result;
	}
}
