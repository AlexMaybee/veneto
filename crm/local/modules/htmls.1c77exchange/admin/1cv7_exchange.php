<?
if (($_REQUEST['type'] == 'sale' && $_REQUEST['mode'] == 'query')||
	($_REQUEST['type'] == 'catalog' && $_REQUEST['mode'] == 'import'))
{
	define('BX_SESSION_ID_CHANGE', false);
	define('BX_SKIP_POST_UNQUOTE', true);
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

	IncludeModuleLangFile(__FILE__);

	$res = CModule::IncludeModuleEx('htmls.1c77exchange');

	if($res == 3) die(utf2win($MESS["V77EXCHANGE_MODULE_DEMO_EXPIRED"]));

	$V7Exchange = new Epages\Exchange\Processing;
	if (!function_exists("utf2win")) {
		function utf2win($str)
		{
			if (strtoupper(LANG_CHARSET) == 'UTF-8')
				return iconv('UTF-8', 'WINDOWS-1251', $str);
			else
				return $str;
		}
	}
	//Bitrix\Main\Diag\Debug::dumpToFile(array('TEST' => "KK"),"","/logParoxod.txt");
}
if($_REQUEST['type'] == 'catalog' && $_REQUEST['mode'] == 'import'){

	CModule::IncludeModule('iblock');
	CModule::IncludeModule('catalog');

	$file = $_REQUEST['filename'];
	if($file == 'file'){
		$V7Exchange->ImportOffLine();
	}
	else{
		$V7Exchange->Import();
	}
	//Bitrix\Main\Diag\Debug::dumpToFile(array('TEST' => $file),"","/logParoxod.txt");

	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");

}
elseif($_REQUEST['type'] == 'sale' && $_REQUEST['mode'] == 'query'){
	CModule::IncludeModule('sale');
	$V7Exchange->Export();
}
else
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/htmls.1c77exchange/admin/1cv7_exchange.php");
}
?>

