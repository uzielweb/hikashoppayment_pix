<?php
/**
 * @package    HikaShop for Joomla!
 * @version    5.0.3
 * @author    hikashop.com
 * @copyright    (C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

// load this plugin language file
$lang = Factory::getLanguage();
$lang->load('plg_hikashoppayment_pix', JPATH_ADMINISTRATOR);
define('CHAVE_PIX_LABEL', Text::_('CHAVE_PIX_LABEL'));
define('BENEFICIARIO_PIX_LABEL', Text::_('BENEFICIARIO_PIX_LABEL'));
define('CIDADE_PIX_LABEL', Text::_('CIDADE_PIX_LABEL'));
define('IDENTIFICADOR_LABEL', Text::_('IDENTIFICADOR_LABEL'));
define('DESCRICAO_LABEL', Text::_('DESCRICAO_LABEL'));
define('QR_CODE_WIDTH', Text::_('QR_CODE_WIDTH'));
?>
<?php
class plgHikashoppaymentpix extends hikashopPaymentPlugin
{
    public $name = 'pix';
    public $multiple = true;
    public $pluginConfig = array(
        'order_status' => array('ORDER_STATUS', 'orderstatus', 'verified'),
        'status_notif_email' => array('ORDER_STATUS_NOTIFICATION', 'boolean', '0'),
        'information' => array('BANK_ACCOUNT_INFORMATION', 'wysiwyg'),
        'return_url' => array('RETURN_URL', 'input'),
        'chave_pix' => array(CHAVE_PIX_LABEL, 'input'),
        'beneficiario_pix' => array(BENEFICIARIO_PIX_LABEL, 'input'),
        'cidade_pix' => array(CIDADE_PIX_LABEL, 'input'),
        'identificador' => array(IDENTIFICADOR_LABEL, 'input'),
        'descricao' => array(DESCRICAO_LABEL, 'input'),
        'qrcode_width' => array(QR_CODE_WIDTH, 'input'),
    );
    public function onAfterOrderConfirm(&$order, &$methods, $method_id)
    {
        parent::onAfterOrderConfirm($order, $methods, $method_id);
        if ($order->order_status != $this->payment_params->order_status) {
            $this->modifyOrder($order->order_id, $this->payment_params->order_status, (bool) @$this->payment_params->status_notif_email, false);
        }

        $this->removeCart = true;
        $this->information = $this->payment_params->information;
        if (preg_match('#^[a-z0-9_]*$#i', $this->information)) {
            $this->information = Text::_($this->information);
        }
        $currencyClass = hikashop_get('class.currency');
        $this->amount = $currencyClass->format($order->order_full_price, $order->order_currency_id);
        $this->order_number = $order->order_number;
        $this->return_url = &$this->payment_params->return_url;
        return $this->showPage('end');
    }
    public function getPaymentDefaultValues(&$element)
    {
        $element->payment_name = Text::_('PIX');
        $pixsvg = URI::root() . 'plugins/hikashoppayment/pix/pix.svg';
        $element->payment_description = '<p>' . Text::_('YOU_CAN_PAY_USING_PIX') . ' <img src="' . $pixsvg . '" alt="PIX" style="width: 120px;"/>' . '</p>';
        $element->payment_images = Text::_('PIX');
        $element->payment_params->information = '';
        $element->payment_params->order_status = 'created';
    }
}
