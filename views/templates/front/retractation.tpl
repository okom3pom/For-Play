{capture name=path}
	<a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">
		{l s='Mon compte' mod='okom_hamon'}
	</a>
	<span class="navigation-pipe">{$navigationPipe}</span>
	<span class="navigation_page">{l s='Formulaire de rétractation'}</span>
{/capture}

<h1 class="page-heading bottom-indent">{l s='Formulaire de rétractation' mod='okom_hamon'}</h1>

<div class="block-center" id="hamon_form">			
			

	<div class="hamon_form_content">
		<div class="hamon_waiting"><img src="{$img_ps_dir}loadingAnimation.gif" alt="{l s='Please wait' mod='okom_hamon'}" /></div>
		<div id="hamon_form_error"></div>
		<div id="hamon_form_succes"></div>
		{if $orders} 
		<form action="{$link->getModuleLink('okom_hamon')}"  id="hamon_frm"  method="post" class="contact-form-box" enctype="multipart/form-data">
			<div class="hamon_form_container">

				<p class="form-group">
					<label for="hamon_order">{l s='Commande' mod='okom_hamon'} <sup class="required">*</sup> :</label>
					<select id="hamon_order" name="hamon_order">
					<option value="">{l s='Choisir une commande' mod='okom_hamon'}</option>
					{if $orders && count($orders)}
						{foreach from=$orders item=order name=myLoop}
						<option value="{$order.id_order|intval}">{Order::getUniqReferenceOf($order.id_order)} - {dateFormat date=$order.date_add full=0}</option> 
						{/foreach}
					{/if}
					</select>
				</p>
				
				
				<div id="products">
				
				</div>

				<div id="return_message" style="display:none;">
					 <p class="form-group" >
						<label for="hamon_question">{l s='Merci de nous aider à améliorer notre service en nous en disant plus sur votre retour.' mod='okom_hamon'} <sup class="required">*</sup> :</label>
						<textarea class="form-control validate grey" data-validate="isMessage" name="hamon_question" id="hamon_question"></textarea>
					</p>
					
					<p class="submit">
					<input type="hidden" name="send" value="1">
					<button id="hamon_submit" class="btn button button-small" name="sendEmail" type="submit">
						<span>{l s='Send' mod='okom_hamon'}</span>
					</button>
					</p>
				</div>
			</div>
		</form>
		{else}
		<p class="alert alert-warning">{l s='Vous n\'avez pas encore passé de commande ou vous n\'avez pas de commande retournable' mod='okom_hamon'}</p>
		{/if}
	</div>
	

</div>

