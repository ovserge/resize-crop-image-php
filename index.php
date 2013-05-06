<?
//error_reporting(E_ALL); // for testing
class Thumbnail
{
    private $w;
    private $h;
    private $filename;
    private $crop;
    private $resultfile;
    
    private $_mime_settings;
    private $_fsave_allowed;
    private $_tname_tpl         = '%s_%sx%s';
    private $_default_width     = 150;
    private $_default_height    = 150;
    private $_jpeg_quality      = 90;
    private $_sess_varname      = 'THUMB';
    private $_default_rotate     = 0;

    public function __construct()
    {
        session_start();
        header('Content-type: ' . $info['mime']);
//    	header('Content-type: text/html'); // for testing
        $this->resultfile = $_SERVER['DOCUMENT_ROOT'].'/ts/cache/'.md5($_GET['name']).'_'.$_GET['w'].'_'.$_GET['h'];

        if(file_exists($this->resultfile)){
			$hnd = fopen($this->resultfile, "r");
			echo fread($hnd, filesize($this->resultfile));
			fclose($hnd);
		}
		exit;

        
        $this->w = abs((int)@$_GET['w']);
        $this->h = abs((int)@$_GET['h']);
        if (!$this->w && !$this->h) {
            // вписать в рамку по умолчанию
            $this->w = $this->_default_width;
            $this->h = $this->_default_height;
        }
        $this->filename = @$_GET['name'];

        $this->crop = isset($_GET['c']) || isset($_GET['tc']);


        $this->_mime_settings = array(
            'image/gif'  => array(
                'ext'       => '.gif',
                'create'    => 'imagecreatefromgif',
                'save'      => array(&$this, '_gif_save'),
            ),
            'image/jpeg'  => array(
                'ext'       => '.jpg',
                'create'    => 'imagecreatefromjpeg',
                'save'      => array(&$this, '_jpeg_save'),
            ),
            'image/pjpeg'  => array(
                'ext'       => '.jpg',
                'create'    => 'imagecreatefromjpeg',
                'save'      => array(&$this, '_jpeg_save'),
            ),
            'image/png'  => array(
                'ext'       => '.png',
                'create'    => 'imagecreatefrompng',
                'save'      => array(&$this, '_png_save'),
            ),
        );
        
        $this->_fsave_allowed = isset($_SESSION[$this->_sess_varname]);
        
        $this->_run();
    }
    private function _run()
    {

        $new_rotate_tmp = $_GET['rotate'];
        if (!$new_rotate_tmp) {
                $new_rotate_tmp = $default_rotate;
        }
        $new_rotate = $new_rotate_tmp;

        if (!file_exists($this->filename) || !is_file($this->filename)) exit;
        $info = getimagesize($this->filename);
        if (!$info || !isset($this->_mime_settings[$info['mime']])) {
            // We can return default img
            //$files = glob("{$name}_*{$ext}");
            //glob("*.txt")

            exit;
        }
        $settings =& $this->_mime_settings[$info['mime']];
        
        $orig_width  = $info[0];
        $orig_height = $info[1];
        $dst_x = $dst_y = 0;
        
        if (!$this->w) {
            // make height
            $new_width  = $this->w = floor($orig_width * $this->h / $orig_height);
            $new_height = $this->h;
        }
        elseif (!$this->h) {
            // make width
            $new_width  = $this->w;
            $new_height = $this->h = floor($orig_height * $this->w / $orig_width);
        }
        elseif ($this->crop) {
            // with crop
            $scaleW = $this->w / $orig_width;
            $scaleH = $this->h / $orig_height;
            $scale = max($scaleW, $scaleH);
            $new_width  = floor($orig_width * $scale);
            $new_height = floor($orig_height * $scale);
            $dst_x = floor(($this->w - $new_width) / 2);
            $dst_y = floor(($this->h - $new_height) / 2);
        }
        else {
            // without crop
            $scaleW = $this->w / $orig_width;
            $scaleH = $this->h / $orig_height;
            $scale = min($scaleW, $scaleH);
            $new_width  = $this->w = floor($orig_width * $scale);
            $new_height = $this->h = floor($orig_height * $scale);
        }
        
        if ($this->w > $orig_width || $this->h > $orig_height) {
            header('Content-type: ' . $info['mime']);
            readfile($this->filename);
            exit;
        }
        
       // $this_filename = imagerotate($this->filename, $new_rotate, 0);

        $thumbFilename = dirname($this->filename) . '/' 
            . sprintf($this->_tname_tpl, basename($this->filename, $settings['ext']), $this->w, $this->h)
            . $settings['ext']
        ;
        
        if (file_exists($thumbFilename) && filemtime($thumbFilename) >= filemtime($this->filename)) {
            header('Content-type: ' . $info['mime']);
            readfile($thumbFilename);
            exit;
        }

        $orig_img = call_user_func($settings['create'], $this->filename);
        $orig_img = imagerotate($orig_img, $new_rotate, 0);
        $tmp_img  = imagecreatetruecolor($this->w, $this->h);
        // Copy and resize old image into new image
        imagecopyresampled(
            $tmp_img, $orig_img, 
            $dst_x, $dst_y, 
            0, 0, /*left_right top_bottom*/ 
            $new_width, $new_height, 
            $orig_width, $orig_height
        );
        
        imagedestroy($orig_img);
   
        call_user_func($settings['save'], $tmp_img, $thumbFilename);
        imagedestroy($tmp_img);
        exit;
    }
    
    private function _gif_save($img, $filename = false)
    {
		imagegif($img, $this->resultfile);
		$hnd = fopen($this->resultfile, "r");
		echo fread($hnd, filesize($this->resultfile));
		fclose($hnd);
    }
    
    private function _jpeg_save($img, $filename = false)
    {
		imagejpeg($img, $this->resultfile, $this->_jpeg_quality);
		$hnd = fopen($this->resultfile, "r");
		echo fread($hnd, filesize($this->resultfile));
		fclose($hnd);
    }
    
    private function _png_save($img, $filename = false)
    {                
		imagepng($img, $this->resultfile);
		$hnd = fopen($this->resultfile, "r");
		echo fread($hnd, filesize($this->resultfile));			
		fclose($hnd);
   }    
}
new Thumbnail;
?>
