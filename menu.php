<?php
/**
 * Builds the Achievo menu, please note that this file is a slightly modified
 * version of the menu.php file in the ATK skel directory. This version
 * contains an extra line that includes the theme.inc file!
 */

  include_once("atk.inc");
  
  atksession();  
  atksecure();
  
  include_once("./theme.inc");
  
  /* get main menuitems */
  include_once($config_atkroot."config.menu.inc");  

  /* first add module menuitems */
  for ($i = 0; $i < count($g_modules); $i++)
  {
    $module = new $g_modules[$i]();
    menuitems($module->getMenuItems());
  }

  if (!isset($atkmenutop)||$atkmenutop=="") $atkmenutop = "main";

  /* output html */
  $g_layout->output("<html>");
  $g_layout->head($txt_app_title);

  $g_layout->body();
  $g_layout->output("<div align='center'>"); 
  $g_layout->ui_top(text("menu_".$atkmenutop));
  $g_layout->output("<br>");

  /* build menu */
  $menu = "";  
  for ($i = 0; $i < count($g_menu[$atkmenutop]); $i++)
  {
    $name = $g_menu[$atkmenutop][$i]["name"];
    $url = $g_menu[$atkmenutop][$i]["url"];
    $enable = $g_menu[$atkmenutop][$i]["enable"];

    /* delimiter ? */
    if ($g_menu[$atkmenutop][$i] == "-") $menu .= "<br>";
    
    /* submenu ? */
    else if (empty($url) && $enable) $menu .= href('menu.php?atkmenutop='.$name,text("menu_$name")).$config_menu_delimiter;
    else if (empty($url) && !$enable) $menu .=text("menu_$name").$config_menu_delimiter;
      
    /* normal menu item */
    else if ($enable) $menu .= href($url,text("menu_$name"),SESSION_NEW,false,'target="main"').$config_menu_delimiter;
    else $menu .= text("menu_$name").$config_menu_delimiter;    
  }
  
  /* previous */
  if ($atkmenutop != "main")
  {
    $parent = $g_menu_parent[$atkmenutop];
    $menu .= $config_menu_delimiter;
    $menu .= href('menu.php?atkmenutop='.$parent,text("back_to").' '.text("menu_$parent")).'<br>';  
  }

  /* bottom */
  $g_layout->output($menu);
  $g_layout->output("<br><br>");
  $g_layout->ui_bottom();
  $g_layout->output("</div></html>"); 
  $g_layout->outputFlush();
?>
