{extends "$layout"}

{block name="content"}
  <section>
       {if($_POST) }
          <p>{l s='You have successfully submitted your payment form.'}</p>
        {/if} 
        {else}
        { Tools::redirect('http://localhost/projects/prestashop/en/cart?action=show'); }
        {/else}
  </section>
{/block}