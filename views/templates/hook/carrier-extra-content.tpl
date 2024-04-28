<span>
    <input id="carrier_insurance" name="carrier_insurance" type="checkbox" value="1" {if $have_insurance}checked="checked"{/if} data-url="{$ajax_url}">
    <span><i class="fa fa-check rtl-no-flip checkbox-checked" aria-hidden="true"></i></span>
    <label class="js-terms" for="carrier_insurance">
        {l s='je souhaite bénéficier de la garantie de livraison' mod='carrier_insurance'}
        {if $amount > 0}
            {l s='pour un montant de %s HT' sprintf=[$amount] mod='carrier_insurance'}
        {/if}
        .
    </label>
    </span>
    {if isset($hb_ci_id_cms) && $hb_ci_id_cms}
        {l s='Je confirme avoir lu les' mod='carrier_insurance'}
        <a href="{Context::getContext()->link->getCMSLink($hb_ci_id_cms)}" target="_blank" rel="nofollow">"{l s='conditions d\'utilisation de la garantie optionnelle du client' mod='carrier_insurance'}"</a>
        {l s='et j\'y adhère sans reserve ' mod='carrier_insurance'}
        <small>
            <a href="{Context::getContext()->link->getCMSLink($hb_ci_id_cms)}" target="_blank" rel="nofollow">
            ({l s='Cliquez ici pour plus d\'information sur la garantie de livraison' mod='carrier_insurance'})
            </a>
        </small>
    {/if}

<br/><br/>