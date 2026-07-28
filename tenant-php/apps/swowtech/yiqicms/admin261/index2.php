<?php
require '../inc/zzz_admin.php';
//db_insert('menu',["pid"=>9, "tid"=>9, "m_name"=>'AI设置', "m_link"=>'?act=aiconfig', "m_order"=>3, "m_level"=>2, "m_onoff"=>1, "m_key"=>'aiconfig']);
db_insert('menu',["pid"=>4, "tid"=>4, "m_name"=>'模板编辑', "m_link"=>'?act=templateedit', "m_order"=>3, "m_level"=>2, "m_onoff"=>1, "m_key"=>'templateedit']);
