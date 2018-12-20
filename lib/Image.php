<?php

namespace Eckinox\Nex;


class Image {
    const ALIGN_CENTER = 'center';
    const ALIGN_TOP = 'top';
    const ALIGN_BOTTOM = 'bottom';
    const ALIGN_LEFT = 'left';
    const ALIGN_RIGHT = 'right';

    protected $mark_name;
    protected $image;
    protected $image_type;
    protected $font_path;
    protected $font_size;
    protected $font_color; // resource from imagecolorallocate
    protected $filename;

    public function __construct() {
        $this->font_path = PUB_PATH . '_default/font/arial.ttf';
        $this->setFontSize(11);
    }

    public function __destruct() {
        $this->unload();
    }
    
    function __clone() {
        $trans = imagecolortransparent($this->image);
        $clone = imagecreatetruecolor($this->getWidth(), $this->getHeight());
        
        imagealphablending($clone, false);
        imagesavealpha($clone, true);
        
        imagecopy($clone, $this->image, 0, 0, 0, 0, $this->getWidth(), $this->getHeight());
        $this->image = $clone;
    }

    public function getResource() {
        return $this->image;
    }

    public function getType() {
        return $this->image_type;
    }

    public function setFont($path) {
        $this->font_path = Nex::skinPath('font' . DIRECTORY_SEPARATOR . $path);

        if (!$this->font_path) {
            trigger_error($path . ' not found in skin ressources.', E_NOTICE);
        }
    }

    public function setFontSize($size) {
        $this->font_size = $size;
    }

    public function setFontColor($color) {
        $this->font_color = $this->createColor($color, $this->image);
    }

    public function from_string($data, $validate_orientation = false) {
        $this->unload();
        
        $this->image = imagecreatefromstring($data);

        if (  $validate_orientation ) {
            file_put_contents($filename = tempnam("/tmp", "exifcheck"), $data);
            
            $this->_exif_orientation($filename);
        }

        $this->image_type = getimagesizefromstring($data)[2];
            
        if ( ! $this->image ) {
            trigger_error("An invalid data stream was given, trying to create it from string failed", \E_USER_WARNING);
        }
        
        return $this;
    }
    
    public function load($filename) {
        $this->filename = $filename;

        $this->unload();

        $this->mark_name = 'ID:' . uniqid() . ' ' . substr($filename, -30);

        #Nex::benchmark('start', 'image.load.' . $this->mark_name);
        #Nex::benchmark('start', 'image.manip.' . $this->mark_name);

        if (preg_match('/^data:([0-9a-z\/]+);base64,(.+)$/i', $filename, $matches)) {
            $this->image_type = $matches[1];
            $data = base64_decode($matches[2]);
            $this->image = @imagecreatefromstring($data);
        } else {
            if (function_exists('exif_imagetype')) {
                $this->image_type = exif_imagetype($filename);
            } else {
                $info = getimagesize($filename);
                $this->image_type = $info[2];
            }

            if ($this->image_type == IMAGETYPE_JPEG) {
                $this->image = @imagecreatefromjpeg($filename);
                $this->_exif_orientation();
            } elseif ($this->image_type == IMAGETYPE_GIF) {
                $this->image = @imagecreatefromgif($filename);
                $this->saveTransparency($this->image);
            } elseif ($this->image_type == IMAGETYPE_PNG) {
                $this->image = @imagecreatefrompng($filename);
                $this->saveTransparency($this->image);
            }
        }
        
        #Nex::benchmark('stop', 'image.load.' . $this->mark_name);

        return $this;
    }

    public function loadFrom($resource) {
        $this->image = $resource;
    }

    public function unload() {
        if ($this->image) {
            # Nex::benchmark('stop', 'image.manip.' . $this->mark_name);

            imagedestroy($this->image);
            $this->filename = null;
            $this->image = null;
            $this->image_type = null;
            $this->filename = null;
        }
    }

    public function saveJpeg($filename, $compression = 100, $permissions = null) {
        $this->image_type = IMAGETYPE_JPEG;

        return $this->save($filename, $compression, $permissions);
    }

    public function save($filename, $compression = 100, $permissions = null) {
        $this->filename = $filename;

        if ($this->image_type == IMAGETYPE_JPEG) {
            $return = imagejpeg($this->image, $filename, $compression);
        } elseif ($this->image_type == IMAGETYPE_GIF) {
            $return = imagegif($this->image, $filename);
        } elseif ($this->image_type == IMAGETYPE_PNG) {
            $return = imagepng($this->image, $filename);
        }

        if ($permissions != null) {
            chmod($filename, $permissions);
        }

        return $return;
    }

    public function output($image_type = IMAGETYPE_JPEG) {
        if ($image_type == IMAGETYPE_JPEG) {
            $return = imagejpeg($this->image);
        } elseif ($image_type == IMAGETYPE_GIF) {
            $return = imagegif($this->image);
        } elseif ($image_type == IMAGETYPE_PNG) {
            $return = imagepng($this->image);
        }

        return $return;
    }

    public function getExtFromType() {
        switch ($this->image_type) {
            case IMAGETYPE_JPEG:
                return '.jpg';
            case IMAGETYPE_GIF:
                return '.gif';
            case IMAGETYPE_PNG:
                return '.png';
        }

        return '';
    }

    public function getWidth() {
        return imagesx($this->image);
    }

    public function getHeight() {
        return imagesy($this->image);
    }

    public function isSquare() {
        return $this->getWidth() == $this->getHeight();
    }

    public function getRatio() {
        return $this->getWidth() / $this->getHeight();
    }

    public function resizeToHeight($height) {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        return $this->resize($width, $height);
    }

    public function resizeToWidth($width) {
        $ratio = $width / $this->getWidth();
        $height = $this->getHeight() * $ratio;
        return $this->resize($width, $height);
    }

    public function cropToWidth($width, $mode = 'center') {
        return $this->crop($width, 999999, $mode);
    }

    public function cropToHeight($height, $mode = 'center') {
        return $this->crop(999999, $height, '', $mode);
    }

    public function crop($width, $height, $xmode = 'center', $ymode = 'center') {
        // Determine crop Width
        if ($this->getWidth() <= $width) {
            $width = $this->getWidth();
            $width_crop = 0;
        } else {
            $width_crop = $this->getWidth() - $width;
        }

        // Determine crop Height
        if ($this->getHeight() <= $height) {
            $height = $this->getHeight();
            $height_crop = 0;
        } else {
            $height_crop = $this->getHeight() - $height;
        }

        // Determine left position
        switch ($xmode) {
            case 'left':
                $x = 0;
                break;
            case 'right':
                $x = $width_crop;
                break;
            case 'center':
            default:
                $x = round($width_crop / 2);
        }

        // Determine top position
        switch ($ymode) {
            case 'top':
                $y = 0;
                break;
            case 'bottom':
                $y = $height_crop;
                break;
            case 'center':
            default:
                $y = round($height_crop / 2);
        }

        $new_image = imagecreatetruecolor($width, $height);
        $this->saveTransparency($new_image);
        imagecopyresampled($new_image, $this->image, -$x, -$y, 0, 0, $this->getWidth(), $this->getHeight(), $this->getWidth(), $this->getHeight());

        $this->image = $new_image;
    }

    public function square($size) {
        $new_width = $size;
        $new_height = $size;

        if ($this->getWidth() > $this->getHeight()) {
            $this->resizeToHeight($size);
            $this->cropToWidth($size);
        } elseif ($this->getHeight() > $this->getWidth()) {
            $this->resizeToWidth($size);
            $this->cropToHeight($size);
        }
    }

    public function fillToWidth($color, $width, $modeX = 'center') {
        $this->fill($color, $width, $this->getHeight(), $modeX);
    }

    public function fillToHeight($color, $height, $modeY = 'center') {
        $this->fill($color, $this->getWidth(), $height, 0, $modeY);
    }

    public function fillSquare($color) {
        $size = ($width = $this->getWidth()) > ($height = $this->getHeight()) ? $width : $height;

        $this->fill($color, $size, $size);
    }

    /**
     * Fill an img with with a background color
     * $posX and $posY can take a unsigned integer (px) or a mode center|left|right|top|bottom
     */
    public function fill($color, $width, $height, $posX = 'center', $posY = 'center') {
        $width = $this->getWidth() > $width ? $this->getWidth() : $width;
        $height = $this->getHeight() > $height ? $this->getHeight() : $height;

        $bg_img = imagecreatetruecolor($width, $height);
        imagefill($bg_img, 0, 0, $this->createColor($color, $bg_img));

        switch ($posX) {
            case 'left':
                $posX = 0;
                break;
            case 'right':
                $posX = $width - $this->getWidth();
                break;
            case 'center':
                $posX = round(($width - $this->getWidth()) / 2);
                break;
        }

        switch ($posY) {
            case 'top':
                $posY = 0;
                break;
            case 'bottom':
                $posY = $height - $this->getHeight();
                break;
            case 'center':
                $posY = round(($height - $this->getHeight()) / 2);
                break;
        }

        $current_img = $this->getResource();
        $this->image = $bg_img;

        $this->mergeWith($current_img, $posX, $posY);
    }

    /**
     * Constrain image by resizing (enlarge or reduce) it and cropping it to keep proportions
     * @param int width
     * @param int height
     */
    public function constrain($width, $height) {
        $image_width = $this->getWidth();
        $image_height = $this->getHeight();

        if ($image_width > $image_height) {
            $ratio = $image_height / $height;

            if ($image_width / $ratio < $width) {
                $this->resizeToHeight($image_height / ($image_width / $width));
                $this->crop($width, $height);
            } else {
                $this->resizeToHeight($height);
                $this->cropToWidth($width);
            }
        } else {
            $ratio = $image_width / $width;

            if ($image_height / $ratio < $height) {
                $this->resizeToWidth($image_width / ($image_height / $height));
                $this->crop($width, $height);
            } else {
                $this->resizeToWidth($width);
                $this->cropToHeight($height);
            }
        }
    }

    public function scale($scale) {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getheight() * $scale / 100;
        return $this->resize($width, $height);
    }

    public function resize($width, $height) {
        $new_image = imagecreatetruecolor($width, $height);
        $this->saveTransparency($new_image);

        $return = imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->image = $new_image;

        return $return;
    }

    public function resizeToMax($width = null, $height = null) {
        if ($width && $this->getWidth() > $width)
            $this->resizeToWidth($width);

        if ($height && $this->getHeight() > $height)
            $this->resizeToHeight($height);
    }

    public function colorAt($x, $y) {
        $retval = imagecolorat($this->image, $x, $y);
        return $retval;
    }

    public function findBackgroundColor($algorithm = 'four-corners', $tolerance = 0.7, $fallback = true) {
        switch (strtolower($algorithm)) {

            # Getting back color testing 4 corners (if they are the same, use that)
            case 'four-corners':
                $pixels = [];
                $pixels[] = $this->colorAt(0, 0);
                $pixels[] = $this->colorAt($this->getWidth() - 1, 0);
                $pixels[] = $this->colorAt(0, $this->getHeight() - 1);
                $pixels[] = $this->colorAt($this->getWidth() - 1, $this->getHeight() - 1);

                $values = array_count_values($pixels);

                reset($values);
                $color = key($values);
                $condition = ($values[$color] / array_sum($values)) >= 0.7;
                break;

            # Find matching backcolor checking for dominant color
            case 'dominant':
                $color_map = [];

                for ($x = 0; $x < $this->getWidth(); $x++) {
                    for ($y = 0; $y < $this->getHeight(); $y++) {
                        $pixel = $this->colorAt($x, $y);
                        isset($color_map[$pixel]) ? $color_map[$pixel] ++ : $color_map[$pixel] = 0;
                    }
                }

                arsort($color_map);
                $color_map = array_keys($color_map);
                $color = array_shift($color_map);
                $condition = true;
                break;
        }

        return $condition ? converter::longToRGB($color) : ($fallback ? $this->findBackgroundColor('dominant', false) : false);
    }

    public function autoRotate() {
        if ($this->image_type != IMAGETYPE_PNG && ($exif = exif_read_data($this->filename)) && isset($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 8:
                    return $this->rotate(90);

                case 3:
                    return $this->rotate(180);

                case 6:
                    return $this->rotate(-90);
            }
        }
    }

    // Anticlickwise rotation
    public function rotate($angle) {
        $this->image = imagerotate($this->image, $angle, 0);

        $this->saveTransparency($this->image);
    }

    public function mergeWith($image, $posX, $posY, $opacity = 100) {
        if (is_string($image)) {
            $imageInst = new self();
            $imageInst->load($image);
        } elseif (is_resource($image)) {
            $imageInst = new self();
            $imageInst->loadFrom($image);
        } else {
            $imageInst = & $image;
        }
        
        # Adjusting position value, from normal integer / float or with percentages
        list($posX, $posY) = array_map(function($item) {
            $pos = trim($item[0]);
            
            if ( substr($pos, -1, 1) === '%' ) {
                $percent = (float)(rtrim($pos, '%') / 100);
                $pos = ( $percent * $item[1] ) - ( $percent <= 0.50 ? 0 : $item[2] );
            }
            
            return round($pos, 0);
        
        }, [ [ $posX, $this->getWidth(), $image->getWidth() ], [$posY, $this->getHeight(), $image->getHeight() ] ]);
        
        if ($posX < 0) {
            $posX = $this->getWidth() + $posX - $imageInst->getWidth();
        }
        if ($posY < 0) {
            $posY = $this->getHeight() + $posY - $imageInst->getHeight();
        }

        if ( $this->image_type == IMAGETYPE_PNG && $imageInst->getType() == IMAGETYPE_PNG ) {
            imagecopymerge($this->image, $imageInst->getResource(), $posX, $posY, 0, 0, $imageInst->getWidth(), $imageInst->getHeight(), $opacity);
        }
        // imagecopy() better support png-24. But doesnt support merging 2 transparency images
        else {
            imagecopy($this->image, $imageInst->getResource(), $posX, $posY, 0, 0, $imageInst->getWidth(), $imageInst->getHeight());
        }

        unset($imageInst);
    }

    public function writeText($text, $posX, $posY, $angle = 0) {
        if ($posX < 0) {
            $posX = $this->getWidth() + $posX;
        }
        if ($posY < 0) {
            $posY = $this->getHeight() + $posY;
        }

        imagettftext($this->image, $this->font_size, $angle, $posX, $posY, $this->font_color, DOC_ROOT . $this->font_path, $text);
    }

    protected function createColor($color, &$image) {
        $color = is_string($color) ? converter::hexToRGB($color) : $color;

        return imagecolorallocate($image, $color[0], $color[1], $color[2]);
    }

    protected function saveTransparency(&$image) {
        switch ($this->image_type) {
            case IMAGETYPE_PNG:
                imagealphablending($image, false);
                imagesavealpha($image, true);
                
                break;
        }
    }
    
    /**
     *  @var $filehandler  This function accepts a stream or a filepath
     *
     */
    protected function _exif_orientation($filehandler) {
        
        $exif = \exif_read_data($filehandler);
        
        if(!empty($exif['Orientation'])) {
            switch($exif['Orientation']) {
                case 8:
                    $this->image = imagerotate($this->image, 90, 0);
                    break;
                case 3:
                    $this->image = imagerotate($this->image, 180, 0);
                    break;
                case 6:
                    $this->image = imagerotate($this->image, -90, 0);
                    break;
            }
        }
        
        return $this;
    }
    
}
