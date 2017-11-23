<?php
/*
*  
* 	Module OKOM_HAMON
*	Envois les conditions de vente par mail lors de la finalisation d'une commande.
*  
*	Author Okom3pom - http://okom3pom.com
*	Version 1.9 - 18/06/2015
* 
*/
if (!defined('_CAN_LOAD_FILES_'))
	exit;

class okom_Hamon extends Module
{
	protected $_html;
	
	public function __construct()
	{
		$this->name = 'okom_hamon';
		$this->tab = 'Tools';
		$this->version = 1.9;
		$this->author = 'Okom3pom';
	
		if(version_compare(_PS_VERSION_, '1.6.0.0', '>='))
		$this->bootstrap = true;		
		
		parent::__construct();

		$this->displayName = $this->l('Lois Hamon');
		$this->description = $this->l('Envoyer les conditions de ventes après la finalisation de la commande.');		
	}

	public function install()
	{
		if (Shop::isFeatureActive())
			Shop::setContext(Shop::CONTEXT_ALL);

		return parent::install()
		&& $this->registerHook('customerAccount')
		&& $this->registerHook('actionValidateOrder')
		&& $this->registerHook('actionObjectCmsUpdateAfter')
		&& $this->registerHook('actionObjectOrderReturnAddAfter')
		&& Configuration::updateValue('OKOM_HAMON_MODE','conditions.pdf')
		&& Configuration::updateValue('OKOM_HAMON_URL',3)
		&& Configuration::updateValue('OKOM_HAMON_EMAIL',Configuration::get('PS_SHOP_EMAIL'));	
	}
	
	public function uninstall()
	{

		Configuration::deleteByName('OKOM_HAMON_MODE');
		Configuration::deleteByName('OKOM_HAMON_URL');
		Configuration::deleteByName('OKOM_HAMON_EMAIL');
	
		return parent::uninstall();
	
	}

	public function getContent()
	{
		
		$shop = new Shop(Shop::getContextShopID());
		if (empty($shop->id))
				$shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
			
		$output = null;
		
		
		if (Tools::isSubmit('submit'.$this->name))
		{
			
			require_once(dirname(__FILE__).'/../../tools/tcpdf/tcpdf.php');								

			$OKOM_HAMON_MODE = strval(Tools::getValue('OKOM_HAMON_MODE'));			
			$OKOM_HAMON_URL = (int)Tools::getValue('OKOM_HAMON_URL');

			if ( Validate::isEmail(Tools::getValue('OKOM_HAMON_EMAIL')) )
					$OKOM_HAMON_EMAIL = strval(Tools::getValue('OKOM_HAMON_EMAIL'));
			
			if (!$output)
			{				

				Configuration::updateValue('OKOM_HAMON_MODE',$OKOM_HAMON_MODE);
				Configuration::updateValue('OKOM_HAMON_URL',$OKOM_HAMON_URL);
				Configuration::updateValue('OKOM_HAMON_EMAIL',$OKOM_HAMON_EMAIL);			
			
				$content = new CMS($OKOM_HAMON_URL, $this->context->language->id);
			
				if( Validate::isLoadedObject($content) )
				{
					$condition = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
					
					
					$condition->SetCreator(PDF_CREATOR);
					$condition->SetAuthor(Configuration::get('PS_SHOP_NAME'));
					$condition->setPrintHeader(false);
					$condition->setPrintFooter(false);
					$condition->SetFont('helvetica', '', 11, '', true);
					$condition->AddPage();							
					$condition->writeHTML($content->content , true, false, true, false, '');
					$condition->Output(_PS_MODULE_DIR_.$this->name.'/'.Configuration::get('OKOM_HAMON_MODE'), 'F');	
					
				}
				else
				$this->_html .= $this->displayConfirmation($this->l('Oups, configuration mise à jour mais pas de CMS pour le pdf'));					
			
				$this->_html .= $this->displayConfirmation($this->l('Settings updated'));			
			
			}
		}
		
		
		if( file_exists(_PS_MODULE_DIR_.$this->name.'/'.Configuration::get('OKOM_HAMON_MODE')) )
		 $link_condition = '<a href="'.$shop->getBaseURL().'modules/'.$this->name.'/'.Configuration::get('OKOM_HAMON_MODE').'">Voir mes conditions en pdf</a>';
		else
		 $link_condition = 'Les conditions n\'existe pas vous devez enregistrer une fois le module.';
			
		
		
		return $this->hlp().$link_condition.$this->_html.$output.$this->renderForm();
	}


	public function renderForm()
	{		
		$icon = 'icon-cogs';
		$type = 'icon'; 
		$date_type = 'datetime';
		$class = '';
		$radio = 'switch';

		if(version_compare(_PS_VERSION_, '1.6.0.0', '<'))
		{
			$icon = _PS_ADMIN_IMG_ .'cog.gif';
			$type = 'image'; 
			$date_type = 'text';
			$class = 't';
			$radio = 'radio';
		}				

			$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Settings'),
				'$type' => $icon
			),			
			
			'input' => array(
			
				array(
					'name' => 'OKOM_HAMON_MODE',
					'type' => 'text',
					'label' => $this->l('Nom du fichier pdf à générer'),
					'desc' => $this->l('Nom du fichier pdf.'),
					'required' => true
					
				),
				array(
					'name' => 'OKOM_HAMON_URL',
					'type' => 'text',
					'label' => $this->l('Id du CMS de vos conditions generales de ventes '),
					'desc' => $this->l('Le plus souvent 3'),
					'required' => true
					
				),
				array(
					'name' => 'OKOM_HAMON_EMAIL',
					'type' => 'text',
					'label' => $this->l('Email à qui sera envoyé le formulaire'),
					'desc' => $this->l(''),
					'required' => true					
				)						
				
			),
			'submit' => array(
				'title' => $this->l('Save'),
			)
		);
		
		$languages = Language::getLanguages(false);
		foreach ($languages as $k => $language)
			$languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');
		

		$helper = new HelperForm();
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		$helper->languages = $languages;
		$helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
		$helper->allow_employee_form_lang = true;
		$helper->title = $this->displayName;
		$helper->submit_action = 'submit'.$this->name;		
		$helper->tpl_vars = array(
			'uri' => $this->getPathUri(),
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
			);

		return $helper->generateForm($fields_form);
	}
	
	protected function getConfigFieldsValues()
	{		
			return array(
				'OKOM_HAMON_URL' => (int)Tools::getValue('OKOM_HAMON_URL', Configuration::get('OKOM_HAMON_URL')),
				'OKOM_HAMON_EMAIL' => strval(Tools::getValue('OKOM_HAMON_EMAIL', Configuration::get('OKOM_HAMON_EMAIL'))),
				'OKOM_HAMON_MODE' => strval(Tools::getValue('OKOM_HAMON_MODE', Configuration::get('OKOM_HAMON_MODE')))
			);
	}	
	
	
    public function hookactionValidateOrder($params)
    {	
	
		if( file_exists(_PS_MODULE_DIR_.$this->name.'/'.Configuration::get('OKOM_HAMON_MODE')) )
		{
			$content = file_get_contents(_PS_MODULE_DIR_.$this->name.'/'.Configuration::get('OKOM_HAMON_MODE'));
			$file_attachement['content'] = $content;
			$file_attachement['name'] = Configuration::get('OKOM_HAMON_MODE');		
			$file_attachement['mime'] = 'application/pdf';
			
			Mail::Send($this->context->language->id, 
			'hamon', 
			$this->l('Ci-joint les conditions de ventes.') ,
			false,
			$params['customer']->email,
			$params['customer']->lastname,
			strval(Configuration::get('OKOM_HAMON_EMAIL')), 
			strval(Configuration::get('PS_SHOP_NAME')),
			$file_attachement,
			null,
			_PS_MODULE_DIR_.$this->name.'/mails/', false,  null, null);
		}
		
	
	}
	
	public function hookactionObjectOrderReturnAddAfter( $params)
	{	
		
		/*
		Mail::Send( 
		(int)Configuration::get('PS_LANG_DEFAULT'), 
		'retour', 
		$this->l('Demande de retour à validé pour : ').strval(Configuration::get('PS_SHOP_NAME')),
		NULL, 
		strval(Configuration::get('OKOM_HAMON_EMAIL')),
		NULL, 
		strval(Configuration::get('PS_SHOP_EMAIL')), 
		strval(Configuration::get('PS_SHOP_NAME')),
		false, 
		NULL, 
		_PS_MODULE_DIR_.$this->name.'/mails/');	
		*/
		
	}
	
	public function hookactionObjectCmsUpdateAfter($params)
	{
		
		require_once(dirname(__FILE__).'/../../tools/tcpdf/tcpdf.php');	
		
		if( $params['object']->id == Configuration::get('OKOM_HAMON_URL') && Validate::isLoadedObject($params['object']) )
		{
				$condition = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
				$condition->AddPage();							
				$condition->writeHTML($params['object']->content[$this->context->language->id] , true, false, true, false, '');
				$condition->Output(_PS_MODULE_DIR_.$this->name.'/'.Configuration::get('OKOM_HAMON_MODE'), 'F');	

		}
	}
	
	public function hookCustomerAccount($params)
	{		
			
		return $this->display(__FILE__, 'my-account.tpl');
		
	}

	
	private function hlp()
	{

			return '<div class="panel">
						<div class="panel-heading"><i class="icon-money"></i> ' . $this->l('Infos') . '</div>
						<div class="table-responsive">
			<div style="text-align: center;">
			<h2>Ce module est vendu 119.99 € par Prestashp !</h2>
			
			'.$this->l('Des idées, besoin d\'aide venez en parler sur le sujet du forum !').'
			<br/><a href="https://www.prestashop.com/forums/topic/394577-module-gratuit-loi-hamon-presta-16/">
			Topic Module Lois Hamon forum Prestashop</a>
			<br/><br/>
			
			'.$this->checkUpdate().'			
			
			<br/> '.$this->l('Help me improve my free modules').'
			<br /><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
			<input name="cmd" type="hidden" value="_s-xclick" /> 
			<input name="encrypted" type="hidden" value="-----BEGIN PKCS7-----MIIHNwYJKoZIhvcNAQcEoIIHKDCCByQCAQExggEwMIIBLAIBADCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwDQYJKoZIhvcNAQEBBQAEgYAeNbZyGCAg7bCFroYHnzumnvKdD0t6l60baFSpOKG3ShjaJncUpCaL4Wr5Jin8x4Ki3BFUUHD/WYDz51vMlvz8rWYJnDbuzkGTkISg7LY/Y/AMzt7FkBQGvuwo4xnefCY2rQvKgpdgXMbtSyX8L6dLKl5ub2Lw9C0t7QWPxXijKDELMAkGBSsOAwIaBQAwgbQGCSqGSIb3DQEHATAUBggqhkiG9w0DBwQI7oNxi1t4ViCAgZAWc4TGj855nDS7uMBGXqrvsXe+BbwCndDMNOdHvxGur53ReAru1rpn4KqqRcaEY44OmI9EuEVWYJ8k4e3WW7hbr3Y5hl7lzY065RW5yuaEWZiRadBS0esKaBnpdaxfjX+WUyPALVOksC9lGL4hYND4TqyKu7CaAetDy6rPeEtj82pTPNnryBVI5EGjSSQ3VoagggOHMIIDgzCCAuygAwIBAgIBADANBgkqhkiG9w0BAQUFADCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20wHhcNMDQwMjEzMTAxMzE1WhcNMzUwMjEzMTAxMzE1WjCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20wgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBAMFHTt38RMxLXJyO2SmS+Ndl72T7oKJ4u4uw+6awntALWh03PewmIJuzbALScsTS4sZoS1fKciBGoh11gIfHzylvkdNe/hJl66/RGqrj5rFb08sAABNTzDTiqqNpJeBsYs/c2aiGozptX2RlnBktH+SUNpAajW724Nv2Wvhif6sFAgMBAAGjge4wgeswHQYDVR0OBBYEFJaffLvGbxe9WT9S1wob7BDWZJRrMIG7BgNVHSMEgbMwgbCAFJaffLvGbxe9WT9S1wob7BDWZJRroYGUpIGRMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbYIBADAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBBQUAA4GBAIFfOlaagFrl71+jq6OKidbWFSE+Q4FqROvdgIONth+8kSK//Y/4ihuE4Ymvzn5ceE3S/iBSQQMjyvb+s2TWbQYDwcp129OPIbD9epdr4tJOUNiSojw7BHwYRiPh58S1xGlFgHFXwrEBb3dgNbMUa+u4qectsMAXpVHnD9wIyfmHMYIBmjCCAZYCAQEwgZQwgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tAgEAMAkGBSsOAwIaBQCgXTAYBgkqhkiG9w0BCQMxCwYJKoZIhvcNAQcBMBwGCSqGSIb3DQEJBTEPFw0xNDA4MDYxMTQyMzdaMCMGCSqGSIb3DQEJBDEWBBQPqvY1DMDeSGUg4viosYx1YE/okTANBgkqhkiG9w0BAQEFAASBgGyiS2D4SqgBqns3QIXX1sxZHUEEP/NYa9s/lLImyOtt+sLd1PM7jMtZBG4hNuYymL1W0CoFJFaXjKqPHX3Nf5jlE1cMlzpOvHNhpW9eZ4/MOi0eIOEQplxz+mvckjRKItbIgdzcNiL83+m+DVmmYKyb0N/QwrOQZBagKhcQWJ6y-----END PKCS7-----" /><input alt="PayPal - la solution de paiement en ligne la plus simple et la plus s&eacute;curis&eacute;e !" name="submit" src="https://www.paypalobjects.com/fr_XC/i/btn/btn_donateCC_LG.gif" type="image" /> 
			<img style="display: block; margin-left: auto; margin-right: auto;" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" alt="" width="1" height="1" border="0" /></form>
			</div>
			
			</div></div>';

	}
	
	public function checkUpdate()
	{		
		if ( function_exists('curl_init') )
		{		
			$url = 'http://www.okom3pom.com/dev-modules/'.$this->name.'.version';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			ob_start();  
			curl_exec($ch);	
			curl_close($ch);
			$version = ob_get_contents();  
			ob_end_clean(); 
			
			if( $version != $this->version )
			return '<h4>'.$this->l('Une nouvelle version du module est disponible sur http://www.okom3pom.com/ ').'</h4> V'.$this->version.' -> V'.$version.'<br/>';
	
		} 
	}		

	
	
}
?>
