<?php
// 添加错误处理和输出缓冲
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

// 检查GD库是否可用
if (!extension_loaded('gd')) {
    header('Content-type:text/html;charset=utf-8');
    die('GD库未安装，无法生成验证码');
}

// 创建错误日志函数
function log_error($message) {
    $log_file = dirname(__DIR__) . '/runtime/error/captcha_error.log';
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message\n", FILE_APPEND);
}

//验证码类
class ValidateCode {
 private $charset = 'abcdefghijkmnpqrstuvwxyz';//随机因子
 private $code;//验证码
 private $codelen = 4;//验证码长度
 private $width = 95;//宽度
 private $height = 34;//高度
 private $img;//图形资源句柄
 private $font;//指定的字体
 private $fontsize = 20;//指定字体大小
 private $fontcolor;//指定字体颜色
 private $font_found = true; // 字体是否找到的标记
 
 //构造方法初始化
 public function __construct() {
  // 更安全的路径处理
  $sitedir = dirname(__DIR__) . '/';
  $this->font = $sitedir . 'plugins/bootstrap/fonts/elephant.ttf';
  
  // 检查字体文件是否存在，如果不存在使用内置字体
  if (!file_exists($this->font)) {
      $this->font_found = false;
      log_error('字体文件不存在: ' . $this->font);
  }
 }
 //生成随机码
 private function createCode() {
  $_len = strlen($this->charset)-1;
  for ($i=0;$i<$this->codelen;$i++) {
   $this->code .= $this->charset[mt_rand(0,$_len)];
  }
 }
 //生成背景
 private function createBg() {
  $this->img = imagecreatetruecolor($this->width, $this->height);
  $color = imagecolorallocate($this->img, mt_rand(157,255), mt_rand(157,255), mt_rand(157,255));
  imagefilledrectangle($this->img,0,$this->height,$this->width,0,$color);
 }
 //生成文字
 private function createFont() {
  $_x = $this->width / $this->codelen;
  for ($i=0;$i<$this->codelen;$i++) {
   $this->fontcolor = imagecolorallocate($this->img, mt_rand(0,156), mt_rand(0,156), mt_rand(0,156));
   
   // 根据字体是否找到选择不同的文字渲染方法
   if ($this->font_found) {
       try {
           // 尝试使用TTF字体
           imagettftext(
               $this->img,
               $this->fontsize,
               mt_rand(-30,30),
               $_x*$i+mt_rand(1,5),
               $this->height / 1.4,
               $this->fontcolor,
               $this->font,
               $this->code[$i]
           );
       } catch (Exception $e) {
           // 如果TTF字体渲染失败，使用内置字体
           log_error('TTF字体渲染失败: ' . $e->getMessage());
           $this->font_found = false;
           imagestring(
               $this->img,
               5, // 字体大小 (1-5)
               $_x*$i+mt_rand(1,5),
               $this->height / 2 - 5,
               $this->code[$i],
               $this->fontcolor
           );
       }
   } else {
       // 使用内置字体
       imagestring(
           $this->img,
           5, // 字体大小 (1-5)
           $_x*$i+mt_rand(1,5),
           $this->height / 2 - 5,
           $this->code[$i],
           $this->fontcolor
       );
   }
  }
 }
 //生成线条、雪花
 private function createLine() {
  //线条
  for ($i=0;$i<6;$i++) {
   $color = imagecolorallocate($this->img,mt_rand(0,156),mt_rand(0,156),mt_rand(0,156));
   imageline($this->img,mt_rand(0,$this->width),mt_rand(0,$this->height),mt_rand(0,$this->width),mt_rand(0,$this->height),$color);
  }
  //雪花
  for ($i=0;$i<100;$i++) {
   $color = imagecolorallocate($this->img,mt_rand(200,255),mt_rand(200,255),mt_rand(200,255));
   imagestring($this->img,mt_rand(1,5),mt_rand(0,$this->width),mt_rand(0,$this->height),'*',$color);
  }
 }
 //输出
 private function outPut() {
  // 清除可能的其他输出
  if (ob_get_length()) {
      ob_clean();
  }
  
  try {
      header('Content-type:image/png');
      imagepng($this->img);
  } catch (Exception $e) {
      log_error('输出验证码失败: ' . $e->getMessage());
      // 如果输出失败，尝试输出一个简单的错误图像
      header('Content-type:image/png');
      $error_img = imagecreatetruecolor(150, 30);
      $bg_color = imagecolorallocate($error_img, 255, 255, 255);
      $text_color = imagecolorallocate($error_img, 255, 0, 0);
      imagefilledrectangle($error_img, 0, 0, 150, 30, $bg_color);
      imagestring($error_img, 3, 10, 10, '验证码生成失败', $text_color);
      imagepng($error_img);
      imagedestroy($error_img);
  } finally {
      // 确保释放资源
      if (isset($this->img) && $this->img) {
          imagedestroy($this->img);
      }
  }
 }
 //对外生成
 public function doimg() {
  try {
      $this->createBg();
      $this->createCode();
      $this->createLine();
      $this->createFont();
      $this->outPut();
  } catch (Exception $e) {
      log_error('生成验证码过程出错: ' . $e->getMessage());
      // 确保有输出，防止空白图像
      header('Content-type:image/png');
      $error_img = imagecreatetruecolor(150, 30);
      $bg_color = imagecolorallocate($error_img, 255, 255, 255);
      $text_color = imagecolorallocate($error_img, 255, 0, 0);
      imagefilledrectangle($error_img, 0, 0, 150, 30, $bg_color);
      imagestring($error_img, 3, 10, 10, '验证码生成失败', $text_color);
      imagepng($error_img);
      imagedestroy($error_img);
  }
 }
 //获取验证码
 public function getCode() {
  return strtolower($this->code);
 }
}

// 主程序调用
if (class_exists('ValidateCode')) {
    try {
        $_vc = new ValidateCode();
        $_vc->doimg();
        $_SESSION['code'] = $_vc->getCode();
    } catch (Exception $e) {
        log_error('验证码实例化失败: ' . $e->getMessage());
        header('Content-type:text/html;charset=utf-8');
        die('验证码生成失败');
    }
} else {
    log_error('验证码类不存在');
    header('Content-type:text/html;charset=utf-8');
    die('验证码类不存在');
}

// 确保输出结束
ob_end_flush();
?>