<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: box.inc.php
//       ver 0.2 2026.Sep.28 H.Tomose
//
// 

global $plugin_box_allowed_styles;
$plugin_box_allowed_styles = [
	// 許容するスタイルの正規表現定義
    'color'   => '/^(#[0-9a-f]{3,8}|(rgb|rgba|hsl|hsla)\([^)]+\)|[a-z]+|transparent|currentColor)$/i',
    'background-color'   => '/^(#[0-9a-f]{3,8}|(rgb|rgba|hsl|hsla)\([^)]+\)|[a-z]+|transparent|currentColor)$/i',
    'display' => '/^(block|inline|inline-block|flex|inline-flex|grid|inline-grid|none|contents|table|table-row|table-cell)$/i',
	'width'   => '/^(([+-]?[0-9]+(\.[0-9]+)?(px|em|rem|%|vh|vw|ch|ex|cm|mm|in|pt|pc)|0|auto|inherit|initial|unset|min-content|max-content|fit-content|stretch)|calc\([0-9a-z% \+\-\*\/\(\)\.]+\))$/i',
	'margin' => '/^(auto|0|(-?\d+(\.\d+)?(px|em|%)))(\s+(auto|0|(-?\d+(\.\d+)?(px|em|%)))){0,3}$/',
	'padding' => '/^(0|\d+(\.\d+)?(px|em|%))(\s+(0|\d+(\.\d+)?(px|em|%))){0,3}$/',
 'border'  => '/^(([+-]?[0-9]+(\.[0-9]+)?(px|em|rem|%|vh|vw|ch|ex|cm|mm|in|pt|pc)|0|thin|medium|thick)|(none|hidden|dotted|dashed|solid|double|groove|ridge|inset|outset)|(#[0-9a-f]{3,8}|(rgb|rgba|hsl|hsla)\([^)]+\)|[a-z]+|transparent|currentColor))(\s+(([+-]?[0-9]+(\.[0-9]+)?(px|em|rem|%|vh|vw|ch|ex|cm|mm|in|pt|pc)|0|thin|medium|thick)|(none|hidden|dotted|dashed|solid|double|groove|ridge|inset|outset)|(#[0-9a-f]{3,8}|(rgb|rgba|hsl|hsla)\([^)]+\)|[a-z]+|transparent|currentColor))){0,2}$/i',

	];
//   	'width' => '/^([0-9]+(px|em|%))$/',

function plugin_box_convert()
{
	static $plugin_box_count =0;
	global 	$plugin_box_allowed_styles;

	$num = func_num_args();
	if ($num == 0) { return 'Usage: #box(<start|end|clear>[,styles...])'; }
	$args = func_get_args();
	$mode = array_shift($args);
	
	$retstr = "";
	static $prms = ""; // パラメータ nextを想定して、start時のパラメータは記憶

	$prmstyles= [];
	$prmstag= [];
	$prmclass = "class='plugin_box'";

	$chars = " \t";

	// modeでスタイル指定しているケース対応。start:xxxで指定。
	if (str_starts_with($mode, "start:")||str_starts_with($mode, "next:")) {
		$prmstag= explode(':', $mode, 2);
		$mode = $prmstag[0];
		//$prmclass .= "_".$prmstag[1];
		$prmclass = "class='plugin_box_".$prmstag[1]."'";
		$prmstag= [];
	}

	if($mode=="start" || $mode=="multi"){
		$prms = "";
		foreach($args as $arg) {

			// PukiWikiの制限回避用：calc[...] を calc(...) に変換
			if (preg_match('/calc\s*\[(.*?)\]/i', $arg)) {
				$arg = preg_replace('/calc\s*\[(.*?)\]/i', 'calc($1)', $arg);
			}

			if (strpos($arg, ':') !== false) {
				// パラメータにコロンあり==スタイル指定。
				list($prop, $val) = array_map('trim', explode(':', $arg, 2));
				// 事前準備した正規表現で妥当な形式かチェック
				if (isset($plugin_box_allowed_styles[$prop])&& preg_match($plugin_box_allowed_styles[$prop], $val)) {	
					$prmstyles[] = $prop.":".$val;
				}
			
			}if (strpos($arg, '=') !== false) {
				//パラメータに=あり==非スタイルの属性。

			}else{
				// 不正なリストなので捨てる
				//再利用の可能性があるので、いちおーちょっと残す。
			}

		}

		if($mode=="multi"){
			//multi==段組みモード。必ずdisplay:flexを後付けする。
			$prmstyles[] ="display:flex";
		}

		if(count($prmstyles)>0){
			// スタイル指定が1つ以上ある。出力用データ構築
			$prms = " style='".implode(';', $prmstyles)."'";
		}

	}


	switch ($mode)
	{
		case "start":
			$plugin_box_count +=1;
			$retstr = "<div ".$prmclass.$prms.">";
			break;
		case "multi":
			$plugin_box_count +=1;
			$retstr = "<div ".$prmclass.$prms.">";
			break;

		case "end":
			if( $plugin_box_count ==0){
				// startなしでend. end多すぎエラーを戻す。
				$retstr = "too much #box(end).";
			}else{
				// 閉じる。
				$retstr = "</div>";
				$plugin_box_count -=1;
				
				$prms="";
			}
			break;

		case "next":
			// end→startを行う。段組み的なもの。
			if( $plugin_box_count <=1){
				// 段組みなので少なくとも「親」「兄弟」の２つが
				// startしていないとダメ。
				// start少なすぎエラーを戻す。
				$retstr = "too few #box(start).";
			}else{
				// 閉じ+次を開く。$prmは前のモノを維持。
				$retstr = "</div><div ".$prmclass.$prms.">";

			}
			break;
			

			break;
		case "clear":
			//末端処理。
			if( $plugin_box_count >0){
				// 閉じられていない。不足分の</div>を積み重ねる

				$retstr = str_repeat("</div>",$plugin_box_count);
				// end少なすぎエラーも追加。
				$retstr .= "alert: too few #box(end): ".($plugin_box_count);
				$plugin_box_count=0;
			}
			break;

		default:
			// なにもしない。
			break;
	}

	return $retstr;


}
//⇒?

?>
