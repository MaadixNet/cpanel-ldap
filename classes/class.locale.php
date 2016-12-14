<?php
/**
* Locale class
*
* @package Cpanel
*/


class CpanelLocale
{
    private $language;

    function __construct()
    {
	if (!isset($_SESSION["language"]))
	    //$this->language = "es_ES";
            $_SESSION["language"]="es_ES";
	else
	    $this->language = $_SESSION["language"];
    }

    public function change_language($new_language)
    {
	global $supported_languages;

	if (array_key_exists($new_language, $supported_languages))
	{
	    $this->language = $new_language;
	    $_SESSION["language"] = $new_language;
	    return true;
	}
	else
	{
	    return false;
	}
    }

    public function get_language()
    {
	return $this->language;
    }
function locale_select()
{
    //require_once __DIR__.('/classes/class.locale.php');
    //global $locale;
    global $supported_languages;

    $tag = '<form method="post" action="" id="lang" name="lang">';
    $tag .= '<select class="form-control set-language" name="language">'."\n";

    foreach ($supported_languages as $l_k => $l_v)
    {   
        $tag .= '<option value="'.$l_k.'"';

        if ($l_k == $this->get_language())
                $tag .= ' selected="selected" ';
        $tag .= ">";
        $tag .= $l_v.'</option>'."\n";
        }
         
    $tag .= "</select>\n";
    $tag .= "</form>\n\n";

    return $tag;
}


}
