<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: box.inc.php
//       ver 0.21 2026.Sep.30 H.Tomose
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

	// multi 段組み用の材料。
	// 「next」指定時は multi 指定のときの書式を使うので、それを保存する。
	static $multiprms = ""; // multi指定用；最初に指定されたパラメータを使う
	static $multiclass  ="class='plugin_box_child'";

	$prmclass  ="class='plugin_box'";

	$prms = "";
	$prmstyles= [];
	$prmstag= [];
	$multimnbaseclass = "class='plugin_box_multi'";

	$chars = " \t";

	// modeでスタイル指定しているケース対応。start:xxxで指定。
	if (str_starts_with($mode, "start:")||str_starts_with($mode, "next:")||str_starts_with($mode, "multi:")) {
		$prmstag= explode(':', $mode, 2);
		$mode = $prmstag[0];

		// スタイルクラス名はh半角英数字のみを許可する。
		// チェックしてマッチしたときのみ、クラス参照する
		if (ctype_alnum($prmstag[1])) {
			$prmclass = "class='plugin_box_".$prmstag[1]."'";
			if( $mode=="multi"){
				$multiclass = $prmclass;
			}
		}

		$prmstag= [];
	}

	// パラメータのチェック
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

		if(count($prmstyles)>0){
			// スタイル指定が1つ以上ある。出力用データ構築
			$prms = " style='".implode(';', $prmstyles)."'";
			if($mode==="multi"){
				// multi 時は「連続使用するパラメータ」に保存。
				$multiprms = $prms;
			}
		}
	}


	switch ($mode)
	{
		case "start":
			$plugin_box_count +=1;
			$retstr = "<div ".$prmclass.$prms.">";
			break;

		case "multi":
			// 段組みモード。親の箱と最初の段落を作る。
			$retstr = "<div ".$multimnbaseclass.">";
			$retstr .= "<div ".$multiclass.$multiprms.">";

			$plugin_box_count +=2;
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
			if( $plugin_box_count <1){
				// 最低一つは「前段落」が startしていないとダメ。
				// start少なすぎエラーを戻す。
				$retstr = "too few #box(start).";
			}else{
				// 閉じ+次を開く。$prmは前のモノを維持。
				$retstr = "</div><div ".$multiclass.$multiprms.">";

			}
			break;
			


		case "multiend":
			if( $plugin_box_count <=1 ){
				// multiは入れ子なので、2段階はないとダメ。
				$retstr = "too few #box(start).";
			}else{
				// 「最後の子」と「大元の親」の２つを閉じる。
				$retstr = "</div></div>";
				$plugin_box_count -=2;
				$multiclass  ="class='plugin_box_child'";
				$multiprms="";
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
				$prmclass  ="class='plugin_box'";
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
