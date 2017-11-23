<?php

include_once(dirname(__FILE__).'/../../okom_hamon.php');

class okom_hamonSearchModuleFrontController extends ModuleFrontController
{
	protected $_html;
	
	public function setMedia()
	{
		
		$module = 'okom_hamon';
		parent::setMedia();
		
		$this->addJS(_MODULE_DIR_.$module.'/views/js/okom_hamon.js');		
		$this->addCSS(_MODULE_DIR_.$module.'/views/css/okom_hamon.css');
		$this->addCSS(_THEME_DIR_.'css/history.css');
		
	}
	
	public function initContent()
	{
		parent::initContent();
		$module = 'okom_hamon';
		$module_instance = new okom_Hamon();

		if( Tools::getValue('ajax') == true )
		{
		
			$id_order = Tools::getValue('hamon_order');
		
			$order = new Order((int)$id_order);	
			
			$order_return = OrderReturn::getOrdersReturn((int)$this->context->customer->id ,(int)$id_order);
			
			if( $order->id_customer != $this->context->customer->id || !Validate::isLoadedObject( $order ) )
			{
				$return['errors'] = $module_instance->l('Merci de choisir une commande','default');
				$return['success'] = (isset($return['errors'])) ? false : $succes;
				die(json_encode($return));
			}
			
			if( $order_return && !empty($order_return) )
			{
			
				$link = new Link();			
				$return['errors'] = $module_instance->l('Vous avez déjà fait une demande pour','default').' <a href="'.$link->getPageLink('order-follow',true).'">'.$module_instance->l('ce retour','default').'</a><br/><br/>';
				$return['success'] = (isset($return['errors'])) ? false : $succes;
				die(json_encode($return));
				
			}
			
			$products = $order->getProducts();

			$this->_html .= '
		
			<div id="order-detail-content" class="table_block table-responsive">
				<table class="table table-bordered">
					<thead>
						<tr>
							<th class="first_item">'.$module_instance->l('A retourner','default').'</th>			
							<th class="item">'.$module_instance->l('Reference','default').'</th>
							<th class="item">'.$module_instance->l('Produit','default').'</th>
							<th class="item">'.$module_instance->l('Quantity','default').'</th>
						</tr>
					</thead>
					<tfoot>';
		
		
		
		
			foreach ( $products as $order_product )
					$this->_html .= '<tr class="item">
						
						<td><input id="cb_['.$order_product['id_order_detail'].']" name="ids_order_detail['.$order_product['id_order_detail'].']" value="'.$order_product['id_order_detail'].'" type="checkbox"></td>
						<td>'.$order_product['reference'].'</td>
						<td>'.$order_product['product_name'].'</td>
						<td class="item">
						
						<input style="display: inline;" class="order_qte_input form-control grey" type="text" size="3" name="order_qte_input['.$order_product['id_order_detail'].']" min="0" max="'.$order_product['product_quantity'].'" > 
						<span> / '.$order_product['product_quantity'].'</span>
						
						</td>


						</tr></tfoot>'; 
			
			$this->_html .= '</table></div>';
			
			$return['success'] = $this->_html;
			
			die(json_encode($return));
			
		}		
		else if( Tools::getValue('send') == 1 )
		{
			
			$return = array();
			$check_qty = true;
			
			$hamon_order = (int)Tools::getValue('hamon_order');
			if (!$hamon_order  || empty($hamon_order) )
				$return['errors'][] = $module_instance->l('Numéro de commande invalide','default');
			
			$hamon_question = strval(Tools::getValue('hamon_question'));
			if (!$hamon_question  || empty( $hamon_question ) || !Validate::isMessage( $hamon_question ))
				$return['errors'][] = $module_instance->l('Le motif de votre retour n\'est pas valide ','default');
						
		
			$customizationQtyInput = Tools::getValue('customization_qty_input');
			$order_qte_input = Tools::getValue('order_qte_input');
			$customizationIds = Tools::getValue('customization_ids');
			$ids_order_detail = Tools::getValue('ids_order_detail');
			
			if( empty($order_qte_input) || !$order_qte_input || empty($ids_order_detail) || !$ids_order_detail )
				$return['errors'][] = $module_instance->l('Merci de sélectionner et saisir la quantité que vous souhaitez retourner.','default');
			
			$order = new Order( $hamon_order );
			
			if (!$order->isReturnable())
				$return['errors'][] = $module_instance->l('Cette commande n\'est pas retournable merci de contacter le service client.','default');
			
			if ($order->id_customer != $this->context->customer->id)
				die(Tools::displayError());
			
			$orderReturn = new OrderReturn();
			$orderReturn->id_customer = (int)$this->context->customer->id;
			$orderReturn->id_order =  (int)$hamon_order;
			$orderReturn->question = htmlspecialchars($hamon_question);
			$orderReturn->state = 1;
			$orderReturn->date_add = date('Y-m-d H:m');

			$check_qty = $orderReturn->checkEnoughProduct($ids_order_detail, $order_qte_input, $customizationIds, $customizationQtyInput);
			
			foreach( $ids_order_detail as $id_order_detail )
				if( $order_qte_input[$id_order_detail] < 1 )
					$return['errors'][] = $module_instance->l('Vous devez saisir une quantité.','default');

			if( $check_qty == false )
				$return['errors'][] = $module_instance->l('Les quantitées retournées ne sont pas bonnes. Avez vous déjà fait une demande de retour  pour ce produit ?','default');
						

		if (!isset($return['errors'])) 
		{	
				
			$orderReturn->add();
			$orderReturn->addReturnDetail($ids_order_detail, $order_qte_input, $customizationIds, $customizationQtyInput);
			
			Hook::exec('actionOrderReturn', array('orderReturn' => $orderReturn));			
		
			$link = new Link();
			$id_lang = (int)$this->context->language->id;
			$iso = Language::getIsoById($id_lang);
			$templateVars = array(
				'{order}' => $hamon_order,				
				'{firstname}' => $this->context->customer->lastname,
				'{email}' => $this->context->customer->email,
				'{message}' => $hamon_question

			);
			
			if (file_exists(_PS_MODULE_DIR_.$this->module->name.'/mails/'.$iso.'/retractation.txt') && file_exists(_PS_MODULE_DIR_.$this->module->name.'/mails/'.$iso.'/retractation.html'))
				if (!Mail::Send( 
													(int)Configuration::get('PS_LANG_DEFAULT'), 
													'retractation', 
													Mail::l('Formulaire de rétractation', $id_lang),
													$templateVars, 
													strval(Configuration::get('OKOM_HAMON_EMAIL')),
													NULL, 
													strval(Configuration::get('PS_SHOP_EMAIL')), 
													strval(Configuration::get('PS_SHOP_NAME')),
													NULL, 
													NULL, 
													_PS_MODULE_DIR_.$this->module->name.'/mails/')
													)
													$return['errors'][] = $this->module->l('Failed to send email');	
				// On confirme le retour au client
				if (!Mail::Send( 
													(int)Configuration::get('PS_LANG_DEFAULT'), 
													'condition-retour', 
													Mail::l('Formulaire de rétractation', $id_lang),
													$templateVars, 
													$this->context->customer->email,
													NULL, 
													strval(Configuration::get('PS_SHOP_EMAIL')), 
													strval(Configuration::get('PS_SHOP_NAME')),
													NULL, 
													NULL, 
													_PS_MODULE_DIR_.$this->module->name.'/mails/')
													)
													$return['errors'][] = $module_instance->l('Failed to send email','default');														
					
		}
		
		$succes = '<div class="alert alert-success">'.$module_instance->l('Formulaire de rétractation bien envoyé. Vous pouvez suivre votre retour dans votre compte.','default').'</div>';
		
		$return['success'] = (isset($return['errors'])) ? false : $succes;
		
		die(json_encode($return));
		
		}
		else 
		{		
							
			$orders = $this->getCustomerOrders($this->context->customer->id);
				
				$this->context->smarty->assign(array(
					'module_dir' => _MODULE_DIR_.$module.'/',
					'orders' => $orders
				));			
				
			$this->setTemplate('retractation.tpl');

		}
	}

	public static function getCustomerOrders($id_customer, $showHiddenStatus = false, Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();

		$nb_day = Configuration::get('PS_ORDER_RETURN_NB_DAYS');
		
		if( $nb_day && $nb_day > 13 )
			$retractation = $nb_day ;
		else
			$retractation = 14 ;

		$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT o.*, (SELECT SUM(od.`product_quantity`) FROM `'._DB_PREFIX_.'order_detail` od WHERE od.`id_order` = o.`id_order`) nb_products
		FROM `'._DB_PREFIX_.'orders` o
		WHERE o.`id_customer` = '.(int)$id_customer.'
		AND o.date_add BETWEEN DATE_SUB(NOW(), INTERVAL '.(int)$retractation .' DAY) AND NOW()
		GROUP BY o.`id_order`
		ORDER BY o.`date_add` DESC');
		if (!$res)
			return array();

		foreach ($res as $key => $val)
		{
			$res2 = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
				SELECT os.`id_order_state`, osl.`name` AS order_state, os.`invoice`, os.`color` as order_state_color
				FROM `'._DB_PREFIX_.'order_history` oh
				LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = oh.`id_order_state`)
				INNER JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int)$context->language->id.')
			WHERE oh.`id_order` = '.(int)($val['id_order']).(!$showHiddenStatus ? ' AND os.`hidden` != 1' : '').'
				ORDER BY oh.`date_add` DESC, oh.`id_order_history` DESC
			LIMIT 1');

			if ($res2)
				$res[$key] = array_merge($res[$key], $res2[0]);

		}
		return $res;
	}	
}

?>
