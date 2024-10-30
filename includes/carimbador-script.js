jQuery(document).ready(function($){
    $('.carimbador-color-field').wpColorPicker();
});


jQuery("#carimbador_botao").click(function(){
  var alinhamento = "";
  if(jQuery("#carimbador_setting_alinhamento_R").attr('checked')) alinhamento = "R";
  if(jQuery("#carimbador_setting_alinhamento_C").attr('checked')) alinhamento = "C";
  if(jQuery("#carimbador_setting_alinhamento_L").attr('checked')) alinhamento = "L";
 
  var valor = jQuery("#carimbador_setting_preview").val();
  var dados = [jQuery("#carimbador_setting_margem").val(),
               jQuery("#carimbador_setting_texto").val(),
               jQuery("#carimbador_setting_cor").val(),
               jQuery("#carimbador_setting_fontsize").val(),
               jQuery("#carimbador_setting_fontstyle").val(),
               jQuery("#carimbador_setting_font").val(),
              alinhamento,
              jQuery("#carimbador_setting_margem_l").val()];
  var data = {
			'action': 'carimbador_preview_arquivo',
			'product_id': valor,
      'dados': dados
		};
  jQuery.post(ajaxurl, data, function(response) {

    //jQuery("#carimbador_preview").html("<pre>"+response+"</pre>");

     jQuery("#carimbador_preview_obj").attr("data",response);

		});
 
})

