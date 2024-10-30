<?php
namespace setasign\Fpdi;

use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObject;
use setasign\Fpdi\PdfParser\Type\PdfNull;

if(!class_exists("Fpdi")){
require_once(dirname( CARIMBADOR_PLUGIN_FILE ) . '/FPDI/fpdf.php');
require_once(dirname( CARIMBADOR_PLUGIN_FILE ) . '/FPDI/src/FpdfTpl.php');
require_once(dirname( CARIMBADOR_PLUGIN_FILE ) . '/FPDI/src/FpdiTrait.php');
require_once(dirname( CARIMBADOR_PLUGIN_FILE ) . '/FPDI/src/Fpdi.php');
require_once(dirname( CARIMBADOR_PLUGIN_FILE ) . '/FPDI/src/autoload.php');
}



class CarimbadorMaluco extends Fpdi{

	public  function inicializa() {
		

	add_action( 'carimbador_salva_arquivo', array( new CarimbadorMaluco(), 'salvaArquivoModificado' ),10,3);
	add_action("carimbador_vizualiza_arquivo",array( new CarimbadorMaluco(), 'carimbador_previsualizaArquivo' ),10,4);
	}
	
	private function hexaPraRGB($hexa){
		$r = hexdec(substr($hexa,1,2));
		$g = hexdec(substr($hexa,3,2));
		$b = hexdec(substr($hexa,5,2));
		return array($r,$g,$b);
	}
	
	private function pegaNome($userId){
		$nome = get_user_meta($userId,"billing_first_name",TRUE)." ".get_user_meta($userId,"billing_last_name",TRUE);
		return $nome;
	}
  
  private function pegaCPF($userId){
		$cpf = get_user_meta($userId,"billing_cpf",TRUE);
    if($cpf==""){$cpf = "nao cadastrado";}
		return $cpf;
	}
  
	
	private function montaTexto($dados){
		
		$user = get_user_by_email($dados[0]);
		$userId = $user->ID; // pagamos o ID do usuario pra montar o nome dele
		$nome = $this->pegaNome($userId);
    $cpf = $this->pegaCPF($userId);
		$texto = get_option('carimbador_setting_texto');
    
		if(!$texto){
			$texto = CARIMABDOR_TEXTO_PADRAO;
		}
		$texto = str_replace("{nome}",$nome,$texto);
		$texto = str_replace("{email}",$dados[0],$texto);
    $texto = str_replace("{pedidopuro}",$dados[1],$texto);
		$texto = str_replace("{pedido}","#".$dados[1],$texto);
    $texto = str_replace("{cpf}",$cpf,$texto);

		return $texto;
		
	}
  public function buscaNomeArquivo($pathArquivo){
 
    $str = explode("/",$pathArquivo);
 
    $i = count($str) - 1;
 
    $nome = $str[$i];

    return $nome;
  }
	/**
  *função que gera preview do arquivo modificado
  * TODO @utilizar a função na area admin
  *
  *
  */
  public function carimbador_previsualizaArquivo($pathArquivo,$nome = "",$ajax = false, $dados = ""){
      
    $pathArquivoTemp = dirname( CARIMBADOR_PLUGIN_FILE )."/temp_files/";
    if($dados!=""){
      $margem = $dados[0];
      $texto = $dados[1];
      $corhex = $dados[2];
      $fontsize = $dados[3];
      $estilo = $dados[4];
      $font = $dados[5];
      $alinhamento = $dados[6];
      $margem_l = $dados[7];
    }
    
  if(!$corhex ){
    $corhex = get_option( 'carimbador_setting_cor' );
    if(!$corhex)
      $corhex = CARIMABDOR_COR_PADRAO;
  }
    $cor = $this->hexaPraRGB($corhex);
	
	if(!$margem)
    if((!$margem = get_option("carimbador_setting_margem")) || 
      (!is_numeric($margem))){
        $margem = CARIMABDOR_MARGEM_PADRAO;
    }
  $margem = (float) $margem;

	if(!$margem_l)
    if((!$margem_l = get_option("carimbador_setting_margem_l")) || 
      (!is_numeric($margem_l))){
        $margem_l = CARIMABDOR_MARGEM_L_PADRAO;
    }
  $margem_l = (float) $margem_l;    

  
  if(!$texto)
    $texto = get_option('carimbador_setting_texto');

  if(!$fontsize)
    if(!$fontsize = get_option("carimbador_setting_fontsize")){
      $fontsize = CARIMABDOR_TAMANHO_PADRAO;
    }

if(!$font)
  if(!$font = get_option("carimbador_setting_font")){
    $font = CARIMABDOR_FONTE_PADRAO;
  }
if(!$estilo)
  if(!$estilo = get_option("carimbador_setting_fontstyle")){
    $estilo = CARIMABDOR_ESTILO_PADRAO;
  }
 if(!$alinhamento)
   if(!$alinhamento = get_option("carimbador_setting_alinhamento")){
     $alinhamento = CARIMABDOR_ALINHAMENTO_PADRAO;
   }
    
  $paginas = $this->setSourceFile($pathArquivo);
  $pagina =1;

  $tplId = self::ImportPage($pagina);
  $dadosArquivo = self::getTemplateSize($tplId);
  $orientacao = $dadosArquivo["orientation"];
  $altura = $dadosArquivo["height"];
  $largura = $dadosArquivo["width"];

    if($alinhamento == "R"){
      $margem_l = $margem_l*-1;
      
    }
    
  self::SetMargins($margem_l,$margem);
  self::AddPage($orientacao,[$largura,$altura]);

  $tpl = self::UseTemplate($tplId);
  $largura = $tpl[0];
  $altura = $tpl[1];



  
  self::SetFont($font, $estilo, $fontsize);
  self::SetTextColor($cor[0], $cor[1], $cor[2]);

  self::Cell($largura,0,$texto,0,1,$alinhamento);
  //self::MultiCell($largura,3,$texto,0,"C");
  if($nome ==""){
    $nome = self::buscaNomeArquivo($pathArquivo);  
  }

    
    $tempFiles =  plugins_url( "../temp_files/_temp_$nome",__FILE__); 
    
		$nomeArquivo = $pathArquivoTemp."_temp_".$nome;
		self::Output($nomeArquivo,"F");
    if($ajax)
    echo $tempFiles;
   
  }
  
  
	/**
	* Fun�ao que cria o arquivo carimbado e for�a o download dele usando os dados passados
	* 
	* 
	* @param string $arquivo
	* @param string $nomeArquivo
	* @param string $texto
	* 
	* @return
	*/
	
	public  function salvaArquivoModificado($arquivo,$nomeArquivo,$dados){
		$corhex = get_option( 'carimbador_setting_cor' );
		if(!$corhex){
			$corhex = CARIMABDOR_COR_PADRAO;
		}
			$fontsize = get_option("carimbador_setting_fontsize");
		if(!$fontsize){
			$fontsize = CARIMABDOR_TAMANHO_PADRAO;
		}
		$font = get_option("carimbador_setting_font");
		if(!$font){
			$font = CARIMABDOR_FONTE_PADRAO;
		}
    $estilo = get_option("carimbador_setting_fontstyle");
    if(!$estilo){
      $estilo = CARIMABDOR_ESTILO_PADRAO;
    }
    if(!$alinhamento = get_option("carimbador_setting_alinhamento")){
       $alinhamento = CARIMABDOR_ALINHAMENTO_PADRAO;
     }
	
 	if((!$margem = get_option("carimbador_setting_margem")) || 
 		(!is_numeric($margem))){
		$margem = CARIMABDOR_MARGEM_PADRAO;
	}
    $margem = (float) $margem;

 if((!$margem_l = get_option("carimbador_setting_margem_l")) || 
  (!is_numeric($margem_l))){
    $margem_l = CARIMABDOR_MARGEM_L_PADRAO;
  }
  $margem_l = (float) $margem_l; 
    
		$cor = $this->hexaPraRGB($corhex);
		
		$texto = $this->montaTexto($dados);
		
		
		$paginas = $this->setSourceFile($arquivo);
		$pagina =1;

		$tplId = self::ImportPage($pagina);
		$dadosArquivo = self::getTemplateSize($tplId);
		$orientacao = $dadosArquivo["orientation"];
		$altura = $dadosArquivo["height"];
		$largura = $dadosArquivo["width"];
   
    if($alinhamento == "R"){
      $margem_l = $margem_l*-1;
      
    }
    
    self::SetMargins($margem_l,$margem);
    self::AddPage($orientacao,[$largura,$altura]);

		$tpl = self::UseTemplate($tplId);
		$largura = $tpl[0];
		$altura = $tpl[1];


   
		self::SetFont($font, $estilo, $fontsize);
		self::SetTextColor($cor[0], $cor[1], $cor[2]);
		
		self::Cell($largura,0,$texto,0,1,$alinhamento);
		//self::MultiCell($largura,3,$texto,0,"C");
		
		while($pagina<$paginas){
			$pagina++;
			$tplId = self::ImportPage($pagina);
			self::AddPage($orientacao,[$largura,$altura]);
			self::UseTemplate($tplId);
			self::Cell($largura,0,$texto,0,1,$alinhamento);
			//self::MultiCell($largura,3,$texto,0,"C");
		}
			
		self::Output($nomeArquivo,"D");
		
	}
	
}
$t = new CarimbadorMaluco();
$t->inicializa();