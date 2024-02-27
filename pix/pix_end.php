<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.0.3
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
$app   = Factory::getApplication();
$sitename = htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8');
?>
<?php
/*
# Biblioteca de funções para geração da linha do Pix copia e cola
# cujo texto é utilizado para a geração do QRCode para recebimento
# de pagamentos através do Pix do Banco Central.
#
#
# Desenvolvido em 2020 por Renato Monteiro Batista - http://renato.ovh
#
# Este código pode ser copiado, modificado, redistribuído
# inclusive comercialmente desde que mantida a refereência ao autor.
*/


function montaPix($px){
   /*
   # Esta rotina monta o código do pix conforme o padrão EMV
   # Todas as linhas são compostas por [ID do campo][Tamanho do campo com dois dígitos][Conteúdo do campo]
   # Caso o campo possua filhos esta função age de maneira recursiva.
   #
   # Autor: Eng. Renato Monteiro Batista
   */
   $ret="";
   foreach ($px as $k => $v) {
     if (!is_array($v)) {
        if ($k == 54) { $v=number_format($v,2,'.',''); } // Formata o campo valor com 2 digitos.
        else { $v=remove_char_especiais($v); }
        $ret.=c2($k).cpm($v).$v;
     }
     else {
       $conteudo=montaPix($v);
       $ret.=c2($k).cpm($conteudo).$conteudo;
     }
   }
   return $ret;
}

function remove_char_especiais($txt){
   /*
   # Esta função retorna somente os caracteres alfanuméricos (a-z,A-Z,0-9) de uma string.
   # Caracteres acentuados são convertidos pelos equivalentes sem acentos.
   # Emojis são removidos, mantém espaços em branco.
   #
   # Autor: Eng. Renato Monteiro Batista
   */
   return preg_replace('/\W /','',remove_acentos($txt));
}

function remove_acentos($texto){
   /*
   # Esta função retorna uma string substituindo os caracteres especiais de acentuação
   # pelos respectivos caracteres não acentuados em português-br.
   #
   # Autor: Eng. Renato Monteiro Batista
   */
   $search = explode(",","à,á,â,ä,æ,ã,å,ā,ç,ć,č,è,é,ê,ë,ē,ė,ę,î,ï,í,ī,į,ì,ł,ñ,ń,ô,ö,ò,ó,œ,ø,ō,õ,ß,ś,š,û,ü,ù,ú,ū,ÿ,ž,ź,ż,À,Á,Â,Ä,Æ,Ã,Å,Ā,Ç,Ć,Č,È,É,Ê,Ë,Ē,Ė,Ę,Î,Ï,Í,Ī,Į,Ì,Ł,Ñ,Ń,Ô,Ö,Ò,Ó,Œ,Ø,Ō,Õ,Ś,Š,Û,Ü,Ù,Ú,Ū,Ÿ,Ž,Ź,Ż");
   $replace =explode(",","a,a,a,a,a,a,a,a,c,c,c,e,e,e,e,e,e,e,i,i,i,i,i,i,l,n,n,o,o,o,o,o,o,o,o,s,s,s,u,u,u,u,u,y,z,z,z,A,A,A,A,A,A,A,A,C,C,C,E,E,E,E,E,E,E,I,I,I,I,I,I,L,N,N,O,O,O,O,O,O,O,O,S,S,U,U,U,U,U,Y,Z,Z,Z");
   return remove_emoji(str_replace($search, $replace, $texto));
}

function remove_emoji($string){
   /*
   # Esta função retorna o conteúdo de uma string removendo oas caracteres especiais
   # usados para representação de emojis.
   #
   */
   return preg_replace('%(?:
   \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
 | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
 | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
)%xs', '  ', $string);      
}


function cpm($tx){
    /*
    # Esta função auxiliar retorna a quantidade de caracteres do texto $tx com dois dígitos.
    #
    # Autor: Renato Monteiro Batista
    */
    if (strlen($tx) > 99) {
      die("Tamanho máximo deve ser 99, inválido: $tx possui " . strlen($tx) . " caracteres.");
    }
    /*
    Não aprecio o uso de die no código, é um tanto deselegante pois envolve matar.
    Mas considerando que 99 realmente é o tamanho máximo aceitável, estou adotando-o.
    Mas aconselho que essa verificação seja feita em outras etapas do código.
    Caso não tenha entendido a problemática consulte  a página 4 do Manual de Padrões para Iniciação do Pix.
    Ou a issue 4 deste projeto: https://github.com/renatomb/php_qrcode_pix/issues/4
    */
    return c2(strlen($tx));
}
 
function c2($input){
    /*
    # Esta função auxiliar trata os casos onde o tamanho do campo for < 10 acrescentando o
    # dígito 0 a esquerda.
    #
    # Autor: Renato Monteiro Batista
    */
    return str_pad($input, 2, "0", STR_PAD_LEFT);
}


function crcChecksum($str) {
   /*
   # Esta função auxiliar calcula o CRC-16/CCITT-FALSE
   #
   # Autor: evilReiko (https://stackoverflow.com/users/134824/evilreiko)
   # Postada originalmente em: https://stackoverflow.com/questions/30035582/how-to-calculate-crc16-ccitt-in-php-hex
   */
  // The PHP version of the JS str.charCodeAt(i)
   function charCodeAt($str, $i) {
      return ord(substr($str, $i, 1));
   }

   $crc = 0xFFFF;
   $strlen = strlen($str);
   for($c = 0; $c < $strlen; $c++) {
      $crc ^= charCodeAt($str, $c) << 8;
      for($i = 0; $i < 8; $i++) {
            if($crc & 0x8000) {
               $crc = ($crc << 1) ^ 0x1021;
            } else {
               $crc = $crc << 1;
            }
      }
   }
   $hex = $crc & 0xFFFF;
   $hex = dechex($hex);
   $hex = strtoupper($hex);
   $hex = str_pad($hex, 4, '0', STR_PAD_LEFT);

   return $hex;
}

?>
<?php
$qrcode_width = $this->payment_params->qrcode_width ? $this->payment_params->qrcode_width : '200';
$chave_pix = $this->payment_params->chave_pix; // A lógica para obter a chave pix pode depender da sua implementação
$valor_pix = $this->amount ? $this->amount : '0.00'; // A lógica para obter o valor pix pode depender da sua implementação
$beneficiario_pix = $this->payment_params->beneficiario_pix; // A lógica para obter o beneficiário pix pode depender da sua implementação
// força o beneficiário a ter somente 25 caracteres desconsiderando caracteres especiais e acentos
$beneficiario_pix = remove_acentos($beneficiario_pix);
$beneficiario_pix = substr($beneficiario_pix, 0, 25);
$cidade_pix = $this->payment_params->cidade_pix; // A lógica para obter a cidade pix pode depender da sua implementação
$identificador = $this->payment_params->identificador; // A lógica para obter o identificador pix pode depender da sua implementação
$descricao = Text::_('ORDER_NUMBER') . ': ' . $this->order_number . ' - ' . strip_tags($this->payment_params->descricao); // A lógica para obter a descrição pix pode depender da sua implementação

$valor_pix = preg_replace("/[^0-9.]/", "", $this->amount);
// // o número é 17530 e precisa ser convertido para 175.30
// $valor_pix = number_format($valor_pix, 2, '.', '');

$valor_pix = number_format($valor_pix, 2, '.', '');
//  ficou 17530 ainda
$valor_pix = $valor_pix/100;

   if (isset($chave_pix) && isset($beneficiario_pix) && isset($cidade_pix)) {
      $chave_pix=strtolower($chave_pix);
      $beneficiario_pix=$beneficiario_pix;
      $cidade_pix=$cidade_pix;
      if (isset($descricao)){
         $descricao=$descricao;
      }
      else { $descricao=''; }
      if ((!isset($identificador)) || (empty($identificador))) {
         $identificador="***";
      }
      else {
         /*
         Atenção: Quando informado pelo recebedor, cada identificador deve ser único (ex.: UUID).
         Os identificadores são usados para a facilitar a conciliação da transação. Na auséncia do
         identificador recomendável o uso de três astericos: ***
         O identificador é limitado a 25 caracteres.
         */
         $identificador=$identificador;
         if (strlen($identificador) > 25) {
            $identificador=substr($identificador,0,25);
         }
      }
      $gerar_qrcode=true;
   }
   else {
      $cidade_pix="SAO PAULO";
      $gerar_qrcode=false;
   }


?>
 <?php
/*
# Exemplo de uso do php_qrcode_pix com descrição dos campos
#
# Desenvolvido em 2020 por Renato Monteiro Batista - http://renato.ovh
#
# Este código pode ser copiado, modificado, redistribuído
# inclusive comercialmente desde que mantidos a referência ao autor.
*/
if ($gerar_qrcode){

   // include "phpqrcode/qrlib.php";
   $px[00]="01"; //Payload Format Indicator, Obrigatório, valor fixo: 01
   // Se o QR Code for para pagamento único (só puder ser utilizado uma vez), descomente a linha a seguir.
   //$px[01]="12"; //Se o valor 12 estiver presente, significa que o BR Code só pode ser utilizado uma vez. 
   $px[26][00]="br.gov.bcb.pix"; //Indica arranjo específico; “00” (GUI) obrigatório e valor fixo: br.gov.bcb.pix
   $px[26][01]=$chave_pix;
   if (!empty($descricao)) {
      /* 
      Não é possível que a chave pix e infoAdicionais cheguem simultaneamente a seus tamanhos máximos potenciais.
      Conforme página 15 do Anexo I - Padrões para Iniciação do PIX  versão 1.2.006.
      */
      $tam_max_descr=99-(4+4+4+14+strlen($chave_pix));
      if (strlen($descricao) > $tam_max_descr) {
         $descricao=substr($descricao,0,$tam_max_descr);
      }
      $px[26][02]=$descricao;
   }
   $px[52]="0000"; //Merchant Category Code “0000” ou MCC ISO18245
   $px[53]="986"; //Moeda, “986” = BRL: real brasileiro - ISO4217
   if ($valor_pix > 0) {
      // Na versão 1.2.006 do Anexo I - Padrões para Iniciação do PIX estabelece o campo valor (54) como um campo opcional.
      $px[54]=$valor_pix;
   }
   $px[58]="BR"; //“BR” – Código de país ISO3166-1 alpha 2
   $px[59]=$beneficiario_pix; //Nome do beneficiário/recebedor. Máximo: 25 caracteres.
   $px[60]=$cidade_pix; //Nome cidade onde é efetuada a transação. Máximo 15 caracteres.
   $px[62][05]=$identificador;
//   $px[62][50][00]="BR.GOV.BCB.BRCODE"; //Payment system specific template - GUI
//   $px[62][50][01]="1.2.006"; //Payment system specific template - versão
   $pix=montaPix($px);
   $pix.="6304"; //Adiciona o campo do CRC no fim da linha do pix.
   $pix.=crcChecksum($pix); //Calcula o checksum CRC16 e acrescenta ao final.
   $linhas=round(strlen($pix)/120)+1;
}
   ?>
<div class="hikashop_pix_end" id="hikashop_pix_end">
    <div class="hikashop_pix_end_message" id="hikashop_pix_end_message">
        <div class="row">
            <div class="col-12">
                <p><?php echo Text::_('ORDER_IS_COMPLETE');?></p>
                <p><?php echo Text::_('THANK_YOU_FOR_PURCHASE');?></p>
                <p><?php echo Text::sprintf('TRANSFER_MAIN_MESSAGE',$this->order_number);?></p>
                <p><?php echo Text::sprintf('PLEASE_TRANSFERT_MONEY',$this->amount);?></p>
                <p><?php echo $this->information;?></p>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card my-3">
                <div class="card-body">
                    <div class="card-title">
                        <h3 class="pix-qr-title"><?php echo Text::_('PIX_QR_TITLE');?></h3>
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <div class="pix-logo">
                                        <img src="<?php echo Uri::root() . 'plugins/hikashoppayment/pix/pix.svg'; ?>"
                                            alt="PIX" class="img-fluid" width="120" />
                                    </div>
                                    <div class="pix-qr-img">
                                        <!-- https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl -->
                                        <img src="<?php echo 'https://chart.googleapis.com/chart?chs='.$qrcode_width.'x'.$qrcode_width.'&cht=qr&chl='.urlencode($pix).'&choe=UTF-8'; ?>"
                                            alt="QRCode" class="img-fluid" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-auto">
                                <div class="form-group">
                                    <p><a href="<?php echo 'https://chart.googleapis.com/chart?chs='.$qrcode_width.'x'.$qrcode_width.'&cht=qr&chl='.urlencode($pix).'&choe=UTF-8'; ?>"
                                            class="btn btn-primary" data-toggle="tooltip" data-placement="top"
                                            title="<?php echo Text::_('DOWNLOAD_QRCODE');?>"><i
                                                class="icon-download"></i></a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card my-3">
                <div class="card-body">
                    <div class="card-title">
                        <h3><?php echo Text::_('PIX_ROW_COPY_PASTE');?></h3>
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <textarea class="text-monospace w-100 form-control" id="brcodepix"
                                        rows="<?php echo $linhas; ?>" onclick="copiar()"><?php echo $pix;?></textarea>
                                </div>
                            </div>
                            <div class="col-md-auto">
                                <div class="form-group">
                                    <p><button type="button" id="clip_btn" class="btn btn-primary" data-toggle="tooltip"
                                            data-placement="top" title="<?php echo Text::_('COPIAR_CODIGO_PIX');?>"
                                            onclick="copiar()"><i class="icon-clipboard"></i></button></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copiar() {
    var copyText = document.getElementById("brcodepix");
    copyText.select();
    copyText.setSelectionRange(0, 99999); /* For mobile devices */
    document.execCommand("copy");
    document.getElementById("clip_btn").innerHTML = '<i class="icon-check"></i>';
}

function reais(v) {
    v = v.replace(/\D/g, "");
    v = v / 100;
    v = v.toFixed(2);
    return v;
}

function mascara(o, f) {
    v_obj = o;
    v_fun = f;
    setTimeout("execmascara()", 1);
}

function execmascara() {
    v_obj.value = v_fun(v_obj.value);
}
$(function() {
    $('[data-toggle="tooltip"]').tooltip()
})
var qrcode = new QRCode("test", {
    text: "<?php echo $pix;?>",
    width: 520,
    height: 520,
    colorDark : "#000000",
    colorLight : "#ffffff",
    correctLevel : QRCode.CorrectLevel.H
});
</script>
<?php
if(!empty($this->return_url)) {
	$doc = Factory::getDocument();
	$doc->addScriptDeclaration("window.hikashop.ready(function(){window.location='".$this->return_url."'});");
}

