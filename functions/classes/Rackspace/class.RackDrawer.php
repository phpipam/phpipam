<?php
/**
 * Class RackDrawer
 * @package GlasOperator\Rack
 */
class RackDrawer extends Common_functions {

    /**
     * rack
     *
     * @var mixed
     * @access private
     */
    private $rack;

    /**
     * template
     *
     * @var resource
     * @access private
     */
    private $template;


    /**
     * Draws rack
     *
     * @access public
     * @param Rack $rack
     * @return void
     */
    public function draw(Rack $rack) {
        $this->rack = $rack;
        $response = file_get_contents(dirname(__FILE__).'/../../css/'.SCRIPT_PREFIX.'/images/blankracks/'.$this->rack->getSpace().'.png', false);
        $this->template = imagecreatefromstring($response);

        $this->drawNameplate();
        $this->drawContents();

        header("Content-type: image/png");
        imagepng($this->template);
        imagedestroy($this->template);
    }

    /**
     * Draws the name plate of the rack itself
     *
     * @access private
     * @return void
     */
    private function drawNameplate() {
        $nameplate = imagecreate(150, 20);
        imagecolorallocate( $nameplate, 255, 255, 255 ); // Allocate a background color (first color assigned)
        $textColour = imagecolorallocate($nameplate, 0, 0, 0);
        $this->imageCenterString($nameplate, $this->rack->getName(), $textColour);
        imagecopy($this->template, $nameplate, 52, 1, 0, 0, 150, 20);
    }

    /**
     * Inserts the passed text in fontsize and color into the passed image
     *
     * @param resource $img
     * @param string $text
     * @param int $color
     */
    private function imageCenterString($img, $text, $color) {
        $font = 0;
        $num = Array( Array(6, -8), Array(4.7, 6), Array(5.6, 12), Array(6.5, 12), Array(7.6, 16), Array(8.5, 16));
        $width = ceil(mb_strlen($text) * 6.6);
        $x = imagesx($img) - $width - 8;
        $y = Imagesy($img) +9;
        // imagestring($img, $font, $x/2, $y/2, $text, $color);
        imagettftext($img, 8, 0, $x/2, $y/2, $color, dirname(__FILE__)."/../../css/".SCRIPT_PREFIX."/fonts/MesloLGS-Regular.ttf", $text );
    }

    /**
     *  Draws a content slot into the result.
     *
     * @access private
     * @return void
     */
    private function drawContents() {
        foreach ($this->rack->getContent() as $content)
        {
            $pixelSize = 20 * $content->getSize();

            $img = imagecreate(200, $pixelSize);
            $this->drawContent($content, $img, $content->getName());

            $yPos = 22 + 20 * ($this->rack->getSpace() - ($content->getStartLocation() + $content->getSize()));

            imagecopy($this->template, $img, 27, $yPos, 0, 0, 200, $pixelSize);
            imagedestroy($img);
        }
    }

    /**
     * Draws rack content.
     *
     * @access private
     * @param RackContent $content
     * @param mixed $img
     * @param mixed $name
     * @return void
     */
    private function drawContent(RackContent $content, $img, $name)
    {
        if ($content->isActive()) {
            imagecolorallocate($img, 207, 232, 255); // Allocate a background color (first color assigned) - active
            $textColour = imagecolorallocate($img, 0, 0, 0);
            $lineColour = imagecolorallocate($img, 122, 137, 150);
        } else {
            imagecolorallocate($img, 230, 230, 230); // Allocate a background color (first color assigned)  - all
            $textColour = imagecolorallocate($img, 0, 0, 0);
            $lineColour = imagecolorallocate($img, 122, 137, 150);
        }

        $this->imageCenterString($img, $name, $textColour);
        imageline($img, 0, 0, 200, 0, $lineColour);
        imageline($img, 0, imagesy($img) - 1, 200, imagesy($img) - 1, $lineColour);
    }
}